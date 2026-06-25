<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommercialOrderExporter
{
    public function __construct(
        private OrderReportQuery $reports,
        private CsvStream $csv
    ) {
    }

    public function download(array $filters): StreamedResponse
    {
        $query = DB::table('orders as orders')
            ->leftJoin('restaurants as restaurants', 'restaurants.id', '=', 'orders.restaurant_id')
            ->whereIn('orders.id', $this->reports->representativeIds());

        $this->reports->apply($query, $filters);

        $rows = $query
            ->selectRaw('DATE(orders.created_at) as report_date')
            ->addSelect('orders.restaurant_id', 'restaurants.name as restaurant_name')
            ->selectRaw('COUNT(*) as orders_count')
            ->selectRaw("SUM(CASE WHEN orders.business_status IN ('delivered','closed','picked_up_by_customer') THEN 1 ELSE 0 END) as completed_count")
            ->selectRaw("SUM(CASE WHEN orders.business_status IN ('cancelled','refunded','no_show') THEN 1 ELSE 0 END) as cancelled_count")
            ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(orders.payment_method,'')) = 'cash' THEN 1 ELSE 0 END) as cash_count")
            ->selectRaw("SUM(CASE WHEN LOWER(COALESCE(orders.payment_method,'')) <> 'cash' THEN 1 ELSE 0 END) as online_count")
            ->selectRaw('SUM(COALESCE(orders.sub_total,0)) as subtotal_amount')
            ->selectRaw('SUM(COALESCE(orders.offer_discount,0)) as discount_amount')
            ->selectRaw('SUM(COALESCE(orders.tax,0)) as tax_amount')
            ->selectRaw('SUM(COALESCE(orders.delivery_charges,0)) as delivery_amount')
            ->selectRaw('SUM(COALESCE(orders.total,0)) as gross_amount')
            ->selectRaw('SUM(COALESCE(orders.admin_commission,0)) as admin_commission_amount')
            ->selectRaw('SUM(COALESCE(orders.restaurant_commission,0)) as restaurant_commission_amount')
            ->selectRaw('AVG(COALESCE(orders.total,0)) as average_basket')
            ->groupBy(DB::raw('DATE(orders.created_at)'), 'orders.restaurant_id', 'restaurants.name')
            ->orderBy('report_date')
            ->orderBy('restaurant_name');

        $headers = [
            'Date', 'Restaurant', 'Commandes', 'Terminées', 'Annulées / remboursées',
            'Cash', 'Paiement en ligne', 'Sous-total cumulé', 'Remises cumulées',
            'Taxes cumulées', 'Livraison cumulée', 'Chiffre d’affaires brut',
            'Commission plateforme', 'Commission restaurant', 'Panier moyen',
        ];

        return $this->csv->download(
            'export-commercial-' . now()->format('Ymd-His') . '.csv',
            $headers,
            function ($output) use ($rows): void {
                foreach ($rows->cursor() as $row) {
                    fputcsv($output, [
                        Carbon::parse($row->report_date)->format('d/m/Y'),
                        $row->restaurant_name ?? 'Restaurant supprimé',
                        (int) $row->orders_count,
                        (int) $row->completed_count,
                        (int) $row->cancelled_count,
                        (int) $row->cash_count,
                        (int) $row->online_count,
                        (float) $row->subtotal_amount,
                        (float) $row->discount_amount,
                        (float) $row->tax_amount,
                        (float) $row->delivery_amount,
                        (float) $row->gross_amount,
                        (float) $row->admin_commission_amount,
                        (float) $row->restaurant_commission_amount,
                        round((float) $row->average_basket, 2),
                    ], ';');
                }
            }
        );
    }
}
