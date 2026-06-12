<?php

namespace App\Jobs;

use App\Domain\Colis\Models\Shipment;
use App\Domain\Colis\Services\ShipmentNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class SendShipmentStatusNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $failOnTimeout = true;

    protected int $shipmentId;

    public function __construct(int $shipmentId)
    {
        $this->shipmentId = $shipmentId;
        $this->onConnection(config('module_queues.modules.colis.connection', 'database'));
        $this->onQueue(config('module_queues.modules.colis.queue', 'colis'));
    }

    public function handle(ShipmentNotificationService $notificationService): void
    {
        $shipment = Shipment::find($this->shipmentId);

        if (! $shipment) {
            return;
        }

        $notificationService->notifyStatusChange($shipment);
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("colis:shipment-status-notification:{$this->shipmentId}"))->expireAfter(120),
        ];
    }

    public function backoff(): array
    {
        return [30, 60, 120];
    }
}
