<?php

namespace App\Jobs;

use App\Payment;
use App\Services\PaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class HandleTransportPaymentCallbackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 90;
    public $failOnTimeout = true;

    protected int $paymentId;
    protected string $provider;
    protected array $payload;

    public function __construct(int $paymentId, string $provider, array $payload)
    {
        $this->paymentId = $paymentId;
        $this->provider = $provider;
        $this->payload = $payload;
        $this->onConnection(config('module_queues.modules.transport.connection', 'database_transport'));
        $this->onQueue(config('module_queues.modules.transport.queue', 'transport'));
    }

    public function handle(PaymentService $paymentService): void
    {
        $payment = Payment::find($this->paymentId);

        if (! $payment || ! $payment->transport_booking_id) {
            return;
        }

        $paymentService->handleCallback($this->provider, $this->payload);
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("transport:payment-callback:{$this->paymentId}"))->expireAfter(180),
        ];
    }

    public function backoff(): array
    {
        return [30, 60, 120];
    }
}
