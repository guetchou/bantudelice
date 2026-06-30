<?php

namespace App\Services;

use App\Driver;
use App\Restaurant;
use Illuminate\Support\Facades\DB;

class PartnerFinancialDashboardService
{
    public function forRestaurant(Restaurant $restaurant): array
    {
        $gross = (float) DB::table('completed_orders')
            ->where('restaurant_id', $restaurant->id)
            ->sum(DB::raw('COALESCE(NULLIF(sub_total, 0), total, 0)'));

        $commissionRate = max(0, (float) ($restaurant->admin_commission ?? 0));
        $commission = round($gross * ($commissionRate / 100), 2);

        $alreadyPaid = (float) DB::table('restaurant_payments')
            ->where('restaurant_id', $restaurant->id)
            ->where('status', 'paid')
            ->sum('payout_amount');
        $alreadyPaid += (float) DB::table('partner_withdrawals')
            ->where('partner_type', 'restaurant')
            ->where('partner_id', $restaurant->id)
            ->where('status', 'paid')
            ->sum('net_amount');

        $reserved = (float) DB::table('restaurant_payments')
            ->where('restaurant_id', $restaurant->id)
            ->where('status', 'pending')
            ->sum('payout_amount');
        $reserved += (float) DB::table('partner_withdrawals')
            ->where('partner_type', 'restaurant')
            ->where('partner_id', $restaurant->id)
            ->whereIn('status', ['created', 'reserved', 'submitted', 'pending', 'unknown'])
            ->sum('net_amount');

        return $this->buildDashboard($gross, $commission, $alreadyPaid, $reserved, [
            'commission' => $commissionRate > 0
                ? 'Somme des commissions plateforme calculees au taux contractuel de ' . $this->formatRate($commissionRate) . '.'
                : 'Aucune commission plateforme activee sur ce profil restaurant.',
            'pending' => 'Montant temporairement non libere par un retrait ou un reversement en cours.',
        ]);
    }

    public function forDeliveryDriver(Driver $driver): array
    {
        $gross = (float) DB::table('deliveries')
            ->join('orders', 'orders.id', '=', 'deliveries.order_id')
            ->where('deliveries.driver_id', $driver->id)
            ->where('deliveries.status', 'DELIVERED')
            ->where(function ($query) {
                $query->where(function ($onlinePayment) {
                    $onlinePayment->where('orders.payment_method', '!=', 'cash')
                        ->where('orders.payment_status', 'paid');
                })->orWhere(function ($cashPayment) {
                    $cashPayment->where('orders.payment_method', 'cash')
                        ->where('orders.cash_collection_status', 'collected')
                        ->whereNotNull('deliveries.cash_collected_at');
                });
            })
            ->sum('deliveries.delivery_fee');

        $alreadyPaid = (float) DB::table('driver_payments')
            ->where('driver_id', $driver->id)
            ->where('status', 'paid')
            ->sum('payout_amount');
        $alreadyPaid += (float) DB::table('partner_withdrawals')
            ->where('partner_type', 'driver')
            ->where('partner_id', $driver->id)
            ->where('status', 'paid')
            ->sum('net_amount');

        $reserved = (float) DB::table('driver_payments')
            ->where('driver_id', $driver->id)
            ->where('status', 'pending')
            ->sum('payout_amount');
        $reserved += (float) DB::table('partner_withdrawals')
            ->where('partner_type', 'driver')
            ->where('partner_id', $driver->id)
            ->whereIn('status', ['created', 'reserved', 'submitted', 'pending', 'unknown'])
            ->sum('net_amount');

        return $this->buildDashboard($gross, 0, $alreadyPaid, $reserved, [
            'commission' => 'Aucune commission plateforme distincte n est configuree sur les frais de livraison.',
            'pending' => 'Montant temporairement non libere par un retrait ou un reversement en cours.',
        ]);
    }

    public function forTransportDriver(Driver $driver): array
    {
        $gross = (float) DB::table('transport_bookings')
            ->where('driver_id', $driver->id)
            ->whereIn('status', ['completed', 'paid', 'closed'])
            ->where(function ($query) {
                $query->where('payment_status', 'paid')
                    ->orWhereNotNull('cash_collected_at');
            })
            ->sum(DB::raw('COALESCE(total_price, actual_price, estimated_price, 0)'));

        return $this->buildDashboard($gross, 0, 0, 0, [
            'commission' => 'Aucune commission plateforme distincte n est configuree sur les courses transport.',
            'paid' => 'Aucun reversement transport distinct n est enregistre.',
            'pending' => 'Aucun reversement transport distinct n est en attente.',
        ]);
    }

    private function buildDashboard(
        float $gross,
        float $commission,
        float $alreadyPaid,
        float $reserved,
        array $overrides = []
    ): array {
        $net = (float) max($gross - $commission, 0);
        $available = (float) max($net - $alreadyPaid - $reserved, 0);

        $position = [
            'gross' => $gross,
            'commission' => $commission,
            'net_earned' => $net,
            'paid' => $alreadyPaid,
            'reserved' => $reserved,
            'available' => $available,
            'currency' => 'XAF',
            'source' => 'legacy_projection',
        ];

        $cards = [
            ['label' => 'Chiffre d’affaires brut', 'amount' => $gross, 'description' => $overrides['gross'] ?? 'Total brut des transactions validees avant commission.', 'formula' => 'Total brut valide.', 'tone' => 'neutral'],
            ['label' => 'Commission plateforme', 'amount' => $commission, 'description' => $overrides['commission'] ?? 'Somme des commissions plateforme.', 'formula' => 'Somme des commissions.', 'tone' => 'warning'],
            ['label' => 'Net partenaire', 'amount' => $net, 'description' => $overrides['net'] ?? 'Montant net du partenaire.', 'formula' => 'Brut - commission.', 'tone' => 'primary'],
            ['label' => 'Déjà payé', 'amount' => $alreadyPaid, 'description' => $overrides['paid'] ?? 'Reversements confirmes.', 'formula' => 'Somme des reversements confirmes.', 'tone' => 'neutral'],
            ['label' => 'Disponible au retrait', 'amount' => $available, 'description' => $overrides['available'] ?? 'Montant immediatement retirable.', 'formula' => 'Net - paye - reserve.', 'tone' => 'success'],
            ['label' => 'Montant réservé', 'amount' => $reserved, 'description' => $overrides['pending'] ?? 'Montant non encore libere.', 'formula' => 'Retraits et reversements en cours.', 'tone' => 'orange'],
        ];

        return [
            'position' => $position,
            'cards' => $cards,
            'rows' => array_chunk($cards, 3),
        ];
    }

    private function formatRate(float $rate): string
    {
        $formatted = number_format($rate, 2, '.', '');
        return rtrim(rtrim($formatted, '0'), '.') . ' %';
    }
}
