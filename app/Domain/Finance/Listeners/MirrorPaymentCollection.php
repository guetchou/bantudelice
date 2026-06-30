<?php

namespace App\Domain\Finance\Listeners;

use App\Domain\Finance\Services\PaymentCollectionMirrorService;
use App\Domain\Payment\Events\PaymentConfirmed;
use App\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class MirrorPaymentCollection
{
    public function handle(PaymentConfirmed $event): void
    {
        if (! config('financial-mirror.collections_enabled', false)) {
            return;
        }

        $paymentId = (int) $event->payment->id;

        DB::afterCommit(function () use ($paymentId): void {
            try {
                $payment = Payment::find($paymentId);

                if (! $payment) {
                    Log::warning('Financial mirror skipped: payment no longer exists.', [
                        'payment_id' => $paymentId,
                    ]);
                    return;
                }

                app(PaymentCollectionMirrorService::class)->mirror($payment);
            } catch (\Throwable $exception) {
                Log::error('Financial collection mirror failed without blocking payment.', [
                    'payment_id' => $paymentId,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }
}
