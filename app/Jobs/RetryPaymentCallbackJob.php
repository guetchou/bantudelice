<?php

namespace App\Jobs;

use App\Payment;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job pour réessayer un callback de paiement qui a échoué
 */
class RetryPaymentCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payment;
    protected $callbackData;

    /**
     * Create a new job instance.
     *
     * @param Payment $payment
     * @param array $callbackData
     */
    public function __construct(Payment $payment, array $callbackData)
    {
        $this->payment = $payment;
        $this->callbackData = $callbackData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Recharger le paiement depuis la DB
        $payment = Payment::find($this->payment->id);
        
        if (!$payment) {
            Log::warning('Paiement introuvable dans RetryPaymentCallbackJob', [
                'payment_id' => $this->payment->id
            ]);
            return;
        }

        // Vérifier que le paiement est toujours en attente
        if ($payment->status !== 'PENDING') {
            Log::info('Paiement déjà traité, retry ignoré', [
                'payment_id' => $payment->id,
                'status' => $payment->status
            ]);
            return;
        }

        try {
            $paymentService = new PaymentService();
            $paymentService->handleCallback($payment->provider, $this->callbackData);
            
            Log::info('Callback retry réussi', [
                'payment_id' => $payment->id,
                'provider' => $payment->provider
            ]);
        } catch (\Exception $e) {
            Log::error('Callback retry échoué', [
                'payment_id' => $payment->id,
                'provider' => $payment->provider,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts()
            ]);
            
            // Si c'est la dernière tentative, marquer comme échec
            if ($this->attempts() >= $this->tries) {
                $payment->update([
                    'status' => 'FAILED',
                    'meta' => array_merge($payment->meta ?? [], [
                        'retry_failed_at' => now()->toIso8601String(),
                        'retry_attempts' => $this->attempts(),
                        'retry_error' => $e->getMessage()
                    ])
                ]);
            } else {
                // Réessayer plus tard
                throw $e;
            }
        }
    }

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff()
    {
        return [60, 300, 900]; // Retry après 1min, 5min, 15min
    }
}

