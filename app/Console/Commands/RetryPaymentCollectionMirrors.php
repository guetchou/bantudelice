<?php

namespace App\Console\Commands;

use App\Domain\Finance\Models\FinancialMirrorEvent;
use App\Domain\Finance\Services\PaymentCollectionMirrorService;
use App\Payment;
use Illuminate\Console\Command;

final class RetryPaymentCollectionMirrors extends Command
{
    protected $signature = 'finance:retry-payment-collection-mirrors {--payment-id=} {--limit=100}';

    protected $description = 'Retry failed payment collection mirror records.';

    public function handle(PaymentCollectionMirrorService $mirror): int
    {
        $paymentId = $this->option('payment-id');

        if ($paymentId !== null) {
            return $this->retryOne((int) $paymentId, $mirror) ? self::SUCCESS : self::FAILURE;
        }

        $limit = min(max((int) $this->option('limit'), 1), 1000);
        $events = FinancialMirrorEvent::query()
            ->where('event_type', 'payment_collection_received')
            ->where('status', 'failed')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $failures = 0;

        foreach ($events as $event) {
            if (! $this->retryOne((int) $event->source_id, $mirror)) {
                $failures++;
            }
        }

        $this->line('Processed: ' . $events->count());
        $this->line('Failed: ' . $failures);

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function retryOne(int $paymentId, PaymentCollectionMirrorService $mirror): bool
    {
        $payment = Payment::find($paymentId);

        if (! $payment) {
            $this->error('Payment not found: ' . $paymentId);
            return false;
        }

        try {
            $event = $mirror->mirror($payment, true);
            $status = $event?->status ?? 'disabled';
            $this->info('Payment ' . $paymentId . ': ' . $status);

            return in_array($status, ['posted', 'skipped'], true);
        } catch (\Throwable $exception) {
            $this->error('Payment ' . $paymentId . ': ' . $exception->getMessage());
            return false;
        }
    }
}
