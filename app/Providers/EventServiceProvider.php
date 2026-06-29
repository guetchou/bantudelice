<?php

namespace App\Providers;

use App\Domain\Colis\Listeners\ShipmentPaymentConfirmed;
use App\Domain\Food\Listeners\FoodOrderPaymentConfirmed;
use App\Domain\Payment\Events\PaymentConfirmed;
use App\Domain\Payment\Listeners\RecordPaymentBusinessTruth;
use App\Domain\Transport\Listeners\TransportPaymentConfirmed;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        PaymentConfirmed::class => [
            RecordPaymentBusinessTruth::class,
            TransportPaymentConfirmed::class,
            ShipmentPaymentConfirmed::class,
            FoodOrderPaymentConfirmed::class,
        ],
    ];

    public function boot()
    {
        parent::boot();
    }
}
