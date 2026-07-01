<?php

namespace App\Console\Commands;

use App\Domain\Payment\Enums\PaymentStatus;
use App\Domain\Payment\Services\PaymentAllocationService;
use App\Order;
use App\Payment;
use Illuminate\Console\Command;

class BackfillPaymentAllocations extends Command
{
    protected $signature = 'payments:backfill-allocations
        {--limit=500 : Nombre maximal de paiements à analyser}
        {--payment-id= : Restreindre le traitement à un paiement}
        {--dry-run : Calculer sans écrire en base}';

    protected $description = 'Affecte les paiements historiques confirmés à leurs obligations métier';

    public function handle(PaymentAllocationService $allocations): int
    {
        $limit = max(1, min(5000, (int) $this->option('limit')));
        $paymentId = $this->option('payment-id');
        $dryRun = (bool) $this->option('dry-run');

        $query = Payment::query()
            ->whereNotNull('order_id')
            ->whereIn('status', [
                'PAID',
                'SUCCESS',
                'SUCCESSFUL',
                'COMPLETED',
                'CAPTURED',
                'APPROVED',
            ])
            ->whereDoesntHave('allocations')
            ->orderBy('id');

        if ($paymentId !== null && $paymentId !== '') {
            $query->whereKey((int) $paymentId);
        }

        $payments = $query->limit($limit)->get();
        $summary = [
            'candidates' => $payments->count(),
            'processed' => 0,
            'allocated_amount' => 0,
            'unallocated_amount' => 0,
            'fully_funded' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        foreach ($payments as $payment) {
            try {
                if (PaymentStatus::fromRaw($payment->status) !== PaymentStatus::PAID) {
                    $summary['skipped']++;
                    continue;
                }

                if ($dryRun) {
                    $result = $this->preview($payment, $allocations);
                } else {
                    $result = $allocations->allocateConfirmedPayment($payment);
                }

                if (! ($result['handled'] ?? false)) {
                    $summary['skipped']++;
                    continue;
                }

                $summary['processed']++;
                $summary['allocated_amount'] += (int) ($result['allocated_amount'] ?? 0);
                $summary['unallocated_amount'] += (int) ($result['unallocated_amount'] ?? 0);
                $summary['fully_funded'] += (int) (($result['fully_funded'] ?? false) === true);

                $this->line(sprintf(
                    '#%d → %s | affecté %d XAF | non affecté %d XAF%s',
                    $payment->id,
                    $result['target_reference'] ?? 'sans cible',
                    (int) ($result['allocated_amount'] ?? 0),
                    (int) ($result['unallocated_amount'] ?? 0),
                    $dryRun ? ' | simulation' : ''
                ));
            } catch (\Throwable $e) {
                $summary['errors']++;
                $this->error('#' . $payment->id . ' : ' . $e->getMessage());
            }
        }

        $this->newLine();
        $this->table(
            ['Candidats', 'Traités', 'Affecté XAF', 'Non affecté XAF', 'Financés', 'Ignorés', 'Erreurs'],
            [[
                $summary['candidates'],
                $summary['processed'],
                $summary['allocated_amount'],
                $summary['unallocated_amount'],
                $summary['fully_funded'],
                $summary['skipped'],
                $summary['errors'],
            ]]
        );

        return $summary['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function preview(Payment $payment, PaymentAllocationService $allocations): array
    {
        $order = Order::query()->find($payment->order_id);

        if (! $order) {
            return [
                'handled' => false,
                'reason' => 'order_not_found',
            ];
        }

        $funding = $allocations->fundingStatusForFoodOrderGroup((string) $order->order_no);
        $paymentAmount = max(0, (int) round((float) $payment->amount));
        $allocatedAmount = min($paymentAmount, (int) $funding['remaining_amount']);

        return [
            'handled' => true,
            'payment_id' => $payment->id,
            'target_reference' => (string) $order->order_no,
            'allocated_amount' => $allocatedAmount,
            'unallocated_amount' => max(0, $paymentAmount - $allocatedAmount),
            'fully_funded' => $funding['due_amount'] > 0
                && ($funding['allocated_amount'] + $allocatedAmount) >= $funding['due_amount'],
        ];
    }
}
