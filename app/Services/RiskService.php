<?php

namespace App\Services;

use App\Order;
use App\RiskAssessment;
use App\SupportTicket;
use Illuminate\Support\Facades\Schema;

class RiskService
{
    public function score(array $signals = []): array
    {
        $score = 0.0;
        $signalsApplied = [];

        foreach ($signals as $key => $value) {
            if ($value === null || $value === false || $value === '') {
                continue;
            }

            $weight = match ($key) {
                'payment_mismatch' => 0.35,
                'geo_mismatch' => 0.30,
                'customer_cancel_history' => 0.18,
                'driver_fake_gps' => 0.40,
                'coupon_abuse' => 0.20,
                'no_address' => 0.15,
                'incident_open' => 0.22,
                default => 0.08,
            };

            $score += $weight;
            $signalsApplied[] = $key;
        }

        $score = min(1.0, round($score, 2));
        $level = $score >= config('commerce.risk.block_threshold', 0.9)
            ? 'critical'
            : ($score >= config('commerce.risk.high_threshold', 0.7) ? 'high' : ($score >= 0.4 ? 'medium' : 'low'));

        return [
            'score' => $score,
            'level' => $level,
            'signals' => $signalsApplied,
        ];
    }

    public function assessOrder(Order $order, array $signals = [], string $action = 'monitor'): ?RiskAssessment
    {
        if (!Schema::hasTable('risk_assessments')) {
            return null;
        }

        $result = $this->score($signals);
        $assessment = RiskAssessment::create([
            'scope' => 'order',
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'order_id' => $order->id,
            'score' => $result['score'],
            'level' => $result['level'],
            'reason' => json_encode($result['signals'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'action' => $action,
            'payload' => array_merge($signals, $result),
        ]);

        $this->maybeOpenSupportTicket($order, $assessment);

        return $assessment;
    }

    protected function maybeOpenSupportTicket(Order $order, RiskAssessment $assessment): void
    {
        if (($assessment->level ?? 'low') === 'low') {
            return;
        }

        if (!config('commerce.support.auto_ticket_on_risk', true)) {
            return;
        }

        if (!Schema::hasTable('support_tickets')) {
            return;
        }

        $priority = in_array($assessment->level, ['high', 'critical'], true) ? 'high' : 'normal';
        $description = sprintf(
            'Score risque: %s / Niveau: %s / Action: %s / Signaux: %s',
            $assessment->score,
            $assessment->level,
            $assessment->action ?? 'monitor',
            $assessment->reason ?? 'n/a'
        );

        app(SupportTicketService::class)->openUnique([
            'module' => 'food',
            'category' => 'risk',
            'priority' => $priority,
            'status' => 'open',
            'title' => 'Risque élevé détecté',
            'description' => $description,
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'order_id' => $order->id,
            'order_no' => $order->order_no,
            'opened_by_type' => 'system',
            'meta' => [
                'risk_assessment_id' => $assessment->id,
                'risk_level' => $assessment->level,
                'risk_score' => $assessment->score,
                'signals' => $assessment->payload ?? [],
            ],
        ]);
    }
}
