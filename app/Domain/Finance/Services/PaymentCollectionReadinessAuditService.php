<?php

namespace App\Domain\Finance\Services;

use App\Domain\Finance\Models\FinancialMirrorEvent;
use App\Payment;

final class PaymentCollectionReadinessAuditService
{
    public function __construct(
        private readonly PaymentCollectionRouteResolver $routes,
    ) {
    }

    public function audit(?string $provider = null, int $sampleLimit = 20): array
    {
        $sampleLimit = min(max($sampleLimit, 1), 200);
        $summary = [
            'paid_count' => 0,
            'paid_amount' => 0,
            'online_count' => 0,
            'online_amount' => 0,
            'cash_count' => 0,
            'cash_amount' => 0,
            'unclassified_count' => 0,
            'unclassified_amount' => 0,
            'eligible_online_count' => 0,
            'eligible_online_amount' => 0,
            'already_posted_count' => 0,
            'unmirrored_eligible_count' => 0,
            'blocked_payment_count' => 0,
            'duplicate_reference_groups' => 0,
            'ready_for_activation' => false,
        ];

        $blockers = [
            'missing_provider' => 0,
            'unsupported_provider' => 0,
            'missing_provider_reference' => 0,
            'non_xaf_currency' => 0,
            'invalid_amount' => 0,
            'failed_mirror' => 0,
            'duplicate_provider_reference' => 0,
        ];
        $routes = [];
        $mirrorStatuses = [
            'absent' => 0,
            'pending' => 0,
            'processing' => 0,
            'posted' => 0,
            'failed' => 0,
            'skipped' => 0,
            'other' => 0,
        ];
        $samples = [];
        $blockedPaymentIds = [];
        $references = [];
        $eligiblePayments = [];

        $query = Payment::query()
            ->where('status', 'PAID')
            ->orderBy('id');

        if ($provider !== null && trim($provider) !== '') {
            $query->whereRaw('LOWER(provider) = ?', [strtolower(trim($provider))]);
        }

        $query->chunkById(200, function ($payments) use (
            &$summary,
            &$blockers,
            &$routes,
            &$mirrorStatuses,
            &$samples,
            &$blockedPaymentIds,
            &$references,
            &$eligiblePayments,
            $sampleLimit
        ): void {
            $mirrorEvents = FinancialMirrorEvent::query()
                ->where('event_type', 'payment_collection_received')
                ->whereIn('source_id', $payments->pluck('id'))
                ->get()
                ->keyBy('source_id');

            foreach ($payments as $payment) {
                $paymentId = (int) $payment->id;
                $amount = $this->integerAmountOrNull($payment->amount);
                $summary['paid_count']++;
                $summary['paid_amount'] += $amount ?? 0;

                $event = $mirrorEvents->get($paymentId);
                $mirrorStatus = $event ? strtolower((string) $event->status) : 'absent';
                $mirrorStatuses[array_key_exists($mirrorStatus, $mirrorStatuses) ? $mirrorStatus : 'other']++;

                $paymentBlockers = [];
                $providerValue = strtolower(trim((string) $payment->provider));

                if ($providerValue === '') {
                    $blockers['missing_provider']++;
                    $paymentBlockers[] = 'missing_provider';
                }

                if (strtoupper(trim((string) $payment->currency)) !== 'XAF') {
                    $blockers['non_xaf_currency']++;
                    $paymentBlockers[] = 'non_xaf_currency';
                }

                if ($amount === null) {
                    $blockers['invalid_amount']++;
                    $paymentBlockers[] = 'invalid_amount';
                }

                try {
                    $route = $this->routes->resolve($payment);
                } catch (\InvalidArgumentException $exception) {
                    $route = null;
                    if ($providerValue !== '') {
                        $blockers['unsupported_provider']++;
                        $paymentBlockers[] = 'unsupported_provider';
                    }
                }

                if ($route === 'cash') {
                    $summary['cash_count']++;
                    $summary['cash_amount'] += $amount ?? 0;
                } elseif ($route !== null) {
                    $summary['online_count']++;
                    $summary['online_amount'] += $amount ?? 0;
                } else {
                    $summary['unclassified_count']++;
                    $summary['unclassified_amount'] += $amount ?? 0;
                }

                if ($route !== null) {
                    $routes[$route] ??= ['count' => 0, 'amount' => 0];
                    $routes[$route]['count']++;
                    $routes[$route]['amount'] += $amount ?? 0;
                }

                if ($route !== null && $route !== 'cash'
                    && trim((string) $payment->provider_reference) === '') {
                    $blockers['missing_provider_reference']++;
                    $paymentBlockers[] = 'missing_provider_reference';
                }

                if ($mirrorStatus === 'failed') {
                    $blockers['failed_mirror']++;
                    $paymentBlockers[] = 'failed_mirror';
                }

                if ($route !== null && $route !== 'cash'
                    && trim((string) $payment->provider_reference) !== '') {
                    $referenceKey = $route . '|' . trim((string) $payment->provider_reference);
                    $references[$referenceKey] ??= [];
                    $references[$referenceKey][] = $paymentId;
                }

                $eligible = $route !== null
                    && $route !== 'cash'
                    && $paymentBlockers === [];

                if ($eligible) {
                    $eligiblePayments[$paymentId] = [
                        'amount' => $amount ?? 0,
                        'mirror_status' => $mirrorStatus,
                    ];
                    $summary['eligible_online_count']++;
                    $summary['eligible_online_amount'] += $amount ?? 0;
                    if ($mirrorStatus === 'posted') {
                        $summary['already_posted_count']++;
                    } else {
                        $summary['unmirrored_eligible_count']++;
                    }
                }

                if ($paymentBlockers !== []) {
                    $blockedPaymentIds[$paymentId] = true;
                    $this->addSample($samples, $sampleLimit, [
                        'payment_id' => $paymentId,
                        'provider' => $payment->provider,
                        'provider_reference' => $payment->provider_reference,
                        'currency' => $payment->currency,
                        'amount' => $payment->amount,
                        'mirror_status' => $mirrorStatus,
                        'blockers' => array_values(array_unique($paymentBlockers)),
                    ]);
                }
            }
        });

        $duplicateReferences = [];
        $duplicatePaymentIds = [];

        foreach ($references as $referenceKey => $paymentIds) {
            $paymentIds = array_values(array_unique($paymentIds));
            if (count($paymentIds) < 2) {
                continue;
            }

            [$route, $reference] = explode('|', $referenceKey, 2);
            $duplicateReferences[] = [
                'route' => $route,
                'provider_reference' => $reference,
                'payment_ids' => $paymentIds,
            ];
            $summary['duplicate_reference_groups']++;

            foreach ($paymentIds as $paymentId) {
                $duplicatePaymentIds[$paymentId] = true;
                $blockedPaymentIds[$paymentId] = true;
            }
        }

        $blockers['duplicate_provider_reference'] = count($duplicatePaymentIds);

        foreach (array_keys($duplicatePaymentIds) as $paymentId) {
            if (! isset($eligiblePayments[$paymentId])) {
                continue;
            }

            $eligible = $eligiblePayments[$paymentId];
            $summary['eligible_online_count']--;
            $summary['eligible_online_amount'] -= $eligible['amount'];

            if ($eligible['mirror_status'] === 'posted') {
                $summary['already_posted_count']--;
            } else {
                $summary['unmirrored_eligible_count']--;
            }
        }

        ksort($routes);
        $summary['blocked_payment_count'] = count($blockedPaymentIds);
        $summary['ready_for_activation'] = $summary['blocked_payment_count'] === 0;

        return [
            'summary' => $summary,
            'blockers' => $blockers,
            'routes' => $routes,
            'mirror_statuses' => $mirrorStatuses,
            'duplicate_references' => $duplicateReferences,
            'samples' => $samples,
        ];
    }

    private function integerAmountOrNull(mixed $amount): ?int
    {
        if (! is_numeric($amount)) {
            return null;
        }

        $numeric = (float) $amount;
        $rounded = (int) round($numeric);

        if ($rounded <= 0 || abs($numeric - $rounded) > 0.0001) {
            return null;
        }

        return $rounded;
    }

    private function addSample(array &$samples, int $limit, array $sample): void
    {
        if (count($samples) < $limit) {
            $samples[] = $sample;
        }
    }
}
