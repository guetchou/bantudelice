<?php

namespace App\Services;

use App\Jobs\AutoAssignDeliveryJob;
use App\Jobs\FinalizeShipmentCodCollectionJob;
use App\Jobs\GenerateShipmentDeliveryOtpJob;
use App\Jobs\HandleShipmentPaymentCallbackJob;
use App\Jobs\HandleTransportPaymentCallbackJob;
use App\Jobs\ProcessOrderJob;
use App\Jobs\RetryPaymentCallbackJob;
use App\Jobs\SendOrderNotificationsJob;
use App\Jobs\SendShipmentStatusNotificationJob;
use App\Jobs\SendTransportStatusNotificationJob;
use Illuminate\Contracts\Bus\Dispatcher;
use InvalidArgumentException;

class ModuleQueueService
{
    public function __construct(protected Dispatcher $dispatcher)
    {
    }

    public function enqueueJob(string $module, string $jobType, array $payload = [])
    {
        $job = $this->makeJob($module, $jobType, $payload);
        $connection = config("module_queues.modules.{$module}.connection", config('module_queues.default_connection'));
        $queue = config("module_queues.modules.{$module}.queue", config('module_queues.default_queue'));

        if (! empty($payload['_delay']) && method_exists($job, 'delay')) {
            $job->delay($payload['_delay']);
        }

        if (method_exists($job, 'onConnection')) {
            $job->onConnection($connection);
        }

        if (method_exists($job, 'onQueue')) {
            $job->onQueue($queue);
        }

        return $this->dispatcher->dispatch($job);
    }

    protected function makeJob(string $module, string $jobType, array $payload)
    {
        return match ($module) {
            'food' => $this->makeFoodJob($jobType, $payload),
            'colis' => $this->makeColisJob($jobType, $payload),
            'transport' => $this->makeTransportJob($jobType, $payload),
            default => throw new InvalidArgumentException("Aucun mapping de job n'est encore defini pour le module {$module}."),
        };
    }

    protected function makeFoodJob(string $jobType, array $payload)
    {
        return match ($jobType) {
            'process_order' => new ProcessOrderJob(
                $payload['user_id'],
                $payload['order_no'],
                $payload['cart_items'],
                $payload['order_data']
            ),
            'auto_assign_delivery' => new AutoAssignDeliveryJob($payload['delivery']),
            'send_order_notifications' => new SendOrderNotificationsJob(
                $payload['user_id'],
                $payload['order_no'],
                $payload['restaurant_id'] ?? null
            ),
            'retry_payment_callback' => new RetryPaymentCallbackJob(
                $payload['payment'],
                $payload['callback_data']
            ),
            default => throw new InvalidArgumentException("Type de job food inconnu: {$jobType}."),
        };
    }

    protected function makeColisJob(string $jobType, array $payload)
    {
        return match ($jobType) {
            'send_shipment_status_notification' => new SendShipmentStatusNotificationJob(
                $payload['shipment_id']
            ),
            'generate_shipment_delivery_otp' => new GenerateShipmentDeliveryOtpJob(
                $payload['shipment_id']
            ),
            'finalize_shipment_cod_collection' => new FinalizeShipmentCodCollectionJob(
                $payload['shipment_id'],
                $payload['context'] ?? []
            ),
            'handle_shipment_payment_callback' => new HandleShipmentPaymentCallbackJob(
                $payload['payment_id'],
                $payload['provider'],
                $payload['payload']
            ),
            default => throw new InvalidArgumentException("Type de job colis inconnu: {$jobType}."),
        };
    }

    protected function makeTransportJob(string $jobType, array $payload)
    {
        return match ($jobType) {
            'send_transport_status_notification' => new SendTransportStatusNotificationJob(
                $payload['booking_id'],
                $payload['notification_type']
            ),
            'handle_transport_payment_callback' => new HandleTransportPaymentCallbackJob(
                $payload['payment_id'],
                $payload['provider'],
                $payload['payload']
            ),
            default => throw new InvalidArgumentException("Type de job transport inconnu: {$jobType}."),
        };
    }
}
