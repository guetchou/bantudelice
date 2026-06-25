<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingOrderExporter
{
    public function __construct(
        private OrderReportQuery $reports,
        private CsvStream $csv
    ) {
    }

    public function download(array $filters): StreamedResponse
    {
        $query = $this->reports->orders($filters)
            ->with([
                'restaurant:id,name',
                'driver:id,name',
                'payment:id,order_id,provider,provider_reference,status,currency',
            ])
            ->orderBy('orders.id');

        $headers = [
            'N° commande', 'Date', 'Restaurant', 'Mode de remise', 'Statut métier',
            'Mode de paiement', 'Statut paiement', 'Prestataire', 'Référence', 'Devise',
            'Sous-total', 'Remise commerciale', 'Taxe', 'Frais de livraison',
            'Pourboire', 'Total', 'Commission plateforme', 'Commission restaurant',
            'Statut collecte cash', 'Cash collecté le', 'Livreur',
        ];

        return $this->csv->download(
            'export-comptable-' . now()->format('Ymd-His') . '.csv',
            $headers,
            function ($output) use ($query): void {
                $query->chunkById(500, function ($orders) use ($output): void {
                    foreach ($orders as $order) {
                        fputcsv($output, [
                            $order->order_no,
                            $this->date($order->created_at),
                            $order->restaurant->name ?? '',
                            $order->fulfillment_mode ?: 'delivery',
                            $order->business_status ?: $order->status,
                            $order->payment_method,
                            $order->payment_status,
                            $order->payment->provider ?? '',
                            $order->payment->provider_reference ?? '',
                            $order->payment->currency ?? 'XAF',
                            (float) ($order->sub_total ?? 0),
                            (float) ($order->offer_discount ?? 0),
                            (float) ($order->tax ?? 0),
                            (float) ($order->delivery_charges ?? 0),
                            (float) ($order->driver_tip ?? 0),
                            (float) ($order->total ?? 0),
                            (float) ($order->admin_commission ?? 0),
                            (float) ($order->restaurant_commission ?? 0),
                            $order->cash_collection_status ?? '',
                            $this->date($order->cash_collected_at),
                            $order->driver->name ?? '',
                        ], ';');
                    }
                }, 'orders.id', 'id');
            }
        );
    }

    private function date($value): string
    {
        return $value
            ? Carbon::parse($value)->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : '';
    }
}
