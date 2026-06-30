<?php

namespace App\Providers;

use App\Domain\Finance\Adapters\PartnerLedgerV2Gateway;
use App\Domain\Finance\Contracts\FinancialLedgerGateway;
use App\Domain\Finance\Listeners\MirrorPaymentCollection;
use App\Domain\Payment\Events\PaymentConfirmed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class FinanceMirrorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(FinancialLedgerGateway::class, PartnerLedgerV2Gateway::class);
    }

    public function boot(): void
    {
        Event::listen(PaymentConfirmed::class, MirrorPaymentCollection::class);
    }
}
