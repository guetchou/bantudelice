<?php

namespace App\Services;

use App\Domain\GePay\Services\GePayGateway;
use App\Domain\GePay\Services\GePayInternalClientResolver;
use App\PartnerWithdrawal;

final class ResilientPartnerWithdrawalService extends PartnerWithdrawalService
{
    public function __construct(
        GePayGateway $gePayGateway,
        GePayInternalClientResolver $gePayResolver,
        private readonly GePayWithdrawalReconciler $gePayWithdrawalReconciler,
    ) {
        parent::__construct($gePayGateway, $gePayResolver);
    }

    public function reconcile(int $withdrawalId): void
    {
        $withdrawal = PartnerWithdrawal::find($withdrawalId);

        if (! $withdrawal || $withdrawal->isPaid() || $withdrawal->isFailed()) {
            return;
        }

        if ($withdrawal->provider === 'gepay') {
            $this->gePayWithdrawalReconciler->reconcile($withdrawal);

            return;
        }

        parent::reconcile($withdrawalId);
    }
}
