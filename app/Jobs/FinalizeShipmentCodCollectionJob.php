<?php

namespace App\Jobs;

use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class FinalizeShipmentCodCollectionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $failOnTimeout = true;

    protected int $shipmentId;
    protected array $context;

    public function __construct(int $shipmentId, array $context = [])
    {
        $this->shipmentId = $shipmentId;
        $this->context = $context;
        $this->onConnection(config('module_queues.modules.colis.connection', 'database_colis'));
        $this->onQueue(config('module_queues.modules.colis.queue', 'colis'));
    }

    public function handle(ShipmentPaymentService $paymentService): void
    {
        $shipment = Shipment::find($this->shipmentId);

        if (! $shipment || $shipment->payment_status !== 'cod_pending' || (float) $shipment->cod_amount <= 0) {
            return;
        }

        $paymentService->markCodCollected($shipment, $this->context);
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("colis:shipment-cod-collection:{$this->shipmentId}"))->expireAfter(180),
        ];
    }

    public function backoff(): array
    {
        return [30, 60, 120];
    }
}
