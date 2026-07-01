<?php

namespace Tests\Feature;

use App\Payment;
use App\Services\DisbursementService;
use App\Services\PaymentReconciliationService;
use App\Services\PaymentService;
use Tests\TestCase;

class PaymentReconciliationRegressionTest extends TestCase
{
    public function test_failed_reconciliation_keeps_provider_reason_and_status_snapshot()
    {
        $payment = new FakeReconciliationPayment([
            'id' => 401,
            'provider' => 'momo',
            'provider_reference' => 'mtn-ref-401',
            'status' => 'PENDING',
            'amount' => 1,
            'currency' => 'XAF',
            'meta' => [],
        ]);

        $service = new TestPaymentReconciliationService([
            'status' => 'FAILED',
            'provider' => 'mtn_momo',
            'message' => 'Statut récupéré',
            'reason' => 'COULD_NOT_PERFORM_TRANSACTION',
            'provider_status' => 'FAILED',
            'data' => [
                'status' => 'FAILED',
                'reason' => 'COULD_NOT_PERFORM_TRANSACTION',
            ],
        ]);

        $result = $service->reconcile($payment);

        $this->assertTrue($result['reconciled']);
        $this->assertSame('FAILED', $result['status']);
        $this->assertSame('FAILED', $payment->status);
        $this->assertSame('COULD_NOT_PERFORM_TRANSACTION', data_get($payment->meta, 'failure_reason'));
        $this->assertSame('MTN MoMo n\'a pas pu finaliser la transaction.', data_get($payment->meta, 'failure_message'));
        $this->assertSame(
            'Demandez au client de confirmer sur son téléphone, puis réessayez si le problème persiste.',
            data_get($payment->meta, 'failure_action')
        );
        $this->assertSame('FAILED', data_get($payment->meta, 'reconciliation_status'));
        $this->assertSame('FAILED', data_get($payment->meta, 'provider_status.provider_status'));
        $this->assertSame('COULD_NOT_PERFORM_TRANSACTION', data_get($payment->meta, 'provider_status.reason'));
        $this->assertSame(
            'Demandez au client de confirmer sur son téléphone, puis réessayez si le problème persiste.',
            data_get($payment->meta, 'provider_status.action')
        );
    }

    public function test_failed_reconciliation_falls_back_to_nested_provider_reason()
    {
        $payment = new FakeReconciliationPayment([
            'id' => 402,
            'provider' => 'momo',
            'provider_reference' => 'mtn-ref-402',
            'status' => 'PENDING',
            'amount' => 5796,
            'currency' => 'XAF',
            'meta' => [],
        ]);

        $service = new TestPaymentReconciliationService([
            'status' => 'FAILED',
            'provider' => 'mtn_momo',
            'message' => 'Statut récupéré',
            'data' => [
                'status' => 'FAILED',
                'reason' => 'LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED',
            ],
        ]);

        $service->reconcile($payment);

        $this->assertSame(
            'LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED',
            data_get($payment->meta, 'failure_reason')
        );
        $this->assertSame(
            'LOW_BALANCE_OR_PAYEE_LIMIT_REACHED_OR_NOT_ALLOWED',
            data_get($payment->meta, 'provider_status.reason')
        );
    }

    public function test_mtn_failure_catalog_exposes_actionable_metadata()
    {
        $metadata = DisbursementService::buildFailureMetadata('mtn_momo', [
            'reason' => 'PAYER_NOT_FOUND',
        ]);

        $this->assertSame('PAYER_NOT_FOUND', $metadata['failure_reason']);
        $this->assertSame('Le numéro MTN MoMo du payeur est introuvable ou inactif.', $metadata['failure_message']);
        $this->assertSame(
            'Vérifiez le numéro et l\'activation du compte MTN MoMo du client.',
            $metadata['failure_action']
        );
    }

    public function test_successful_reconciliation_passes_provider_context_to_payment_service()
    {
        $payment = new FakeReconciliationPayment([
            'id' => 403,
            'provider' => 'momo',
            'provider_reference' => 'mtn-ref-403',
            'status' => 'PENDING',
            'amount' => 1,
            'currency' => 'XAF',
            'meta' => [],
        ]);

        $paymentService = new CapturingReconciliationPaymentService();
        $service = new TestPaymentReconciliationService([
            'status' => 'PAID',
            'provider' => 'mtn_momo',
            'message' => 'Paiement confirmé',
            'provider_status' => 'SUCCESSFUL',
            'data' => [
                'status' => 'SUCCESSFUL',
                'amount' => 1,
            ],
        ], $paymentService);

        $result = $service->reconcile($payment);

        $this->assertTrue($result['reconciled']);
        $this->assertSame('RECONCILED', $result['status']);
        $this->assertSame('PAID', $payment->status);
        $this->assertSame('SUCCESSFUL', data_get($paymentService->capturedCallbackData, 'provider_status.provider_status'));
        $this->assertSame('RECONCILED', data_get($payment->meta, 'reconciliation_status'));
        $this->assertSame('SUCCESSFUL', data_get($payment->meta, 'provider_status.provider_status'));
    }

    public function test_failed_diagnostic_backfill_updates_missing_failure_metadata()
    {
        $payment = new FakeReconciliationPayment([
            'id' => 404,
            'provider' => 'momo',
            'provider_reference' => 'mtn-ref-404',
            'status' => 'FAILED',
            'amount' => 1,
            'currency' => 'XAF',
            'meta' => [
                'failure_reason' => 'Statut récupéré',
            ],
        ]);

        $service = new BackfillPaymentReconciliationService([
            'status' => 'FAILED',
            'provider' => 'mtn_momo',
            'provider_status' => 'FAILED',
            'reason' => 'PAYER_NOT_FOUND',
            'data' => [
                'status' => 'FAILED',
                'reason' => 'PAYER_NOT_FOUND',
            ],
        ], [$payment]);

        $result = $service->backfillFailedPaymentDiagnostics(10);

        $this->assertSame(1, $result['processed']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame('PAYER_NOT_FOUND', data_get($payment->meta, 'failure_reason'));
        $this->assertSame('Le numéro MTN MoMo du payeur est introuvable ou inactif.', data_get($payment->meta, 'failure_message'));
        $this->assertSame('Vérifiez le numéro et l\'activation du compte MTN MoMo du client.', data_get($payment->meta, 'failure_action'));
    }

    public function test_failed_diagnostic_backfill_uses_manual_note_when_provider_status_is_unavailable()
    {
        $payment = new FakeReconciliationPayment([
            'id' => 405,
            'provider' => 'momo',
            'provider_reference' => 'mtn-ref-405',
            'status' => 'FAILED',
            'amount' => 1,
            'currency' => 'XAF',
            'meta' => [
                'manual_note' => 'Marked FAILED after MTN returned HTML request rejection page during checkout launch.',
            ],
        ]);

        $service = new BackfillPaymentReconciliationService([
            'status' => 'ERROR',
            'provider' => 'mtn_momo',
            'error' => 'Impossible de vérifier le statut',
        ], [$payment]);

        $result = $service->backfillFailedPaymentDiagnostics(10);

        $this->assertSame(1, $result['updated']);
        $this->assertSame('MTN_STATUS_UNAVAILABLE', data_get($payment->meta, 'failure_reason'));
        $this->assertSame(
            'Marked FAILED after MTN returned HTML request rejection page during checkout launch.',
            data_get($payment->meta, 'failure_message')
        );
        $this->assertSame(
            'Relancer uniquement après confirmation de l’absence de débit.',
            data_get($payment->meta, 'failure_action')
        );
    }
}

class FakeReconciliationPayment extends Payment
{
    public function update(array $attributes = [], array $options = []): bool
    {
        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return true;
    }

    public function fresh($with = [])
    {
        return $this;
    }
}

class CapturingReconciliationPaymentService extends PaymentService
{
    public array $capturedCallbackData = [];

    public function markPaymentAsPaid($payment, array $callbackData = []): void
    {
        $this->capturedCallbackData = $callbackData;

        $payment->update([
            'status' => 'PAID',
            'meta' => array_merge($payment->meta ?? [], [
                'callback' => $callbackData,
                'paid_at' => now()->toIso8601String(),
            ]),
        ]);
    }
}

class TestPaymentReconciliationService extends PaymentReconciliationService
{
    public function __construct(
        private array $providerStatus,
        private ?PaymentService $paymentService = null
    ) {
    }

    protected function getProviderStatus(Payment $payment): array
    {
        return $this->providerStatus;
    }

    protected function logReconciliation(Payment $payment, string $status, string $message): void
    {
    }

    protected function makePaymentService(): PaymentService
    {
        return $this->paymentService ?? new CapturingReconciliationPaymentService();
    }
}

class BackfillPaymentReconciliationService extends TestPaymentReconciliationService
{
    public function __construct(
        array $providerStatus,
        private array $payments
    ) {
        parent::__construct($providerStatus);
    }

    protected function loadFailedDiagnosticCandidates(int $limit, ?int $paymentId = null)
    {
        $payments = $this->payments;

        if ($paymentId !== null) {
            $payments = array_values(array_filter($payments, fn ($payment) => $payment->id === $paymentId));
        }

        return collect(array_slice($payments, 0, $limit));
    }
}
