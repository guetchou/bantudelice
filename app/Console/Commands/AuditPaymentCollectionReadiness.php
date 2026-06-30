<?php

namespace App\Console\Commands;

use App\Domain\Finance\Services\PaymentCollectionReadinessAuditService;
use Illuminate\Console\Command;

final class AuditPaymentCollectionReadiness extends Command
{
    protected $signature = 'finance:audit-payment-collection-readiness
        {--provider=}
        {--sample=20}
        {--json}
        {--fail-on-blockers}';

    protected $description = 'Audit PAID payments before enabling the collection mirror.';

    public function handle(PaymentCollectionReadinessAuditService $audit): int
    {
        $result = $audit->audit(
            provider: $this->option('provider'),
            sampleLimit: (int) $this->option('sample'),
        );

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderSummary($result);
        }

        if ($this->option('fail-on-blockers')
            && ! data_get($result, 'summary.ready_for_activation', false)) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function renderSummary(array $result): void
    {
        $summary = $result['summary'];

        $this->table(['Metric', 'Value'], [
            ['paid_count', $summary['paid_count']],
            ['paid_amount_xaf', $summary['paid_amount']],
            ['online_count', $summary['online_count']],
            ['cash_count', $summary['cash_count']],
            ['unclassified_count', $summary['unclassified_count']],
            ['eligible_online_count', $summary['eligible_online_count']],
            ['eligible_online_amount_xaf', $summary['eligible_online_amount']],
            ['already_posted_count', $summary['already_posted_count']],
            ['unmirrored_eligible_count', $summary['unmirrored_eligible_count']],
            ['blocked_payment_count', $summary['blocked_payment_count']],
            ['duplicate_reference_groups', $summary['duplicate_reference_groups']],
            ['ready_for_activation', $summary['ready_for_activation'] ? 'YES' : 'NO'],
        ]);

        $this->line('Routes');
        $this->table(
            ['Route', 'Count', 'Amount XAF'],
            collect($result['routes'])
                ->map(fn (array $row, string $route) => [$route, $row['count'], $row['amount']])
                ->values()
                ->all()
        );

        $this->line('Blockers');
        $this->table(
            ['Blocker', 'Count'],
            collect($result['blockers'])
                ->map(fn (int $count, string $name) => [$name, $count])
                ->values()
                ->all()
        );

        $this->line('Mirror statuses');
        $this->table(
            ['Status', 'Count'],
            collect($result['mirror_statuses'])
                ->map(fn (int $count, string $status) => [$status, $count])
                ->values()
                ->all()
        );

        foreach ($result['duplicate_references'] as $duplicate) {
            $this->warn(sprintf(
                'Duplicate %s reference %s on payments %s',
                $duplicate['route'],
                $duplicate['provider_reference'],
                implode(',', $duplicate['payment_ids'])
            ));
        }

        foreach ($result['samples'] as $sample) {
            $this->warn(sprintf(
                'Payment %d: %s',
                $sample['payment_id'],
                implode(',', $sample['blockers'])
            ));
        }
    }
}
