<?php

namespace Tests\Feature;

use App\Domain\Payment\Enums\FinancialAccountType;
use App\Domain\Payment\Enums\JournalEntryStatus;
use App\Domain\Payment\Enums\JournalEntryType;
use App\Domain\Payment\Enums\LedgerDirection;
use App\Domain\Payment\Services\FinancialAccountService;
use App\Domain\Payment\Services\FinancialJournalService;
use App\Domain\Payment\Services\FinancialPositionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class FinancialJournalServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_posts_a_balanced_idempotent_entry(): void
    {
        $accounts = app(FinancialAccountService::class);
        $journal = app(FinancialJournalService::class);

        $cash = $accounts->getOrCreate(FinancialAccountType::CASH_CLEARING);
        $suspense = $accounts->getOrCreate(FinancialAccountType::SUSPENSE);

        $lines = [
            ['account' => $cash, 'direction' => LedgerDirection::DEBIT, 'amount' => 10000],
            ['account' => $suspense, 'direction' => LedgerDirection::CREDIT, 'amount' => 10000],
        ];

        $entry = $journal->post(
            'collection:provider-ref-001',
            JournalEntryType::COLLECTION_SETTLEMENT,
            $lines,
            ['reference' => 'provider-ref-001']
        );
        $secondCall = $journal->post(
            'collection:provider-ref-001',
            JournalEntryType::COLLECTION_SETTLEMENT,
            $lines,
            ['reference' => 'provider-ref-001']
        );

        $this->assertSame($entry->id, $secondCall->id);
        $this->assertSame(JournalEntryStatus::POSTED, $entry->status);
        $this->assertCount(2, $entry->lines);
        $this->assertTrue($entry->isBalanced());
        $this->assertDatabaseCount('financial_journal_entries', 1);
    }

    public function test_it_rejects_an_unbalanced_entry(): void
    {
        $accounts = app(FinancialAccountService::class);
        $journal = app(FinancialJournalService::class);

        $cash = $accounts->getOrCreate(FinancialAccountType::CASH_CLEARING);
        $suspense = $accounts->getOrCreate(FinancialAccountType::SUSPENSE);

        $this->expectException(LogicException::class);

        $journal->post(
            'collection:unbalanced',
            JournalEntryType::COLLECTION_SETTLEMENT,
            [
                ['account' => $cash, 'direction' => 'debit', 'amount' => 10000],
                ['account' => $suspense, 'direction' => 'credit', 'amount' => 9000],
            ]
        );
    }

    public function test_reversal_keeps_the_audit_trail_and_neutralizes_the_position(): void
    {
        $accounts = app(FinancialAccountService::class);
        $journal = app(FinancialJournalService::class);
        $positions = app(FinancialPositionService::class);

        $cash = $accounts->getOrCreate(FinancialAccountType::CASH_CLEARING);
        $suspense = $accounts->getOrCreate(FinancialAccountType::SUSPENSE);

        $entry = $journal->post(
            'collection:provider-ref-002',
            JournalEntryType::COLLECTION_SETTLEMENT,
            [
                ['account' => $cash, 'direction' => 'debit', 'amount' => 15000],
                ['account' => $suspense, 'direction' => 'credit', 'amount' => 15000],
            ]
        );

        $before = $positions->summary();
        $this->assertSame(15000, $before['cash_clearing']);
        $this->assertSame(15000, $before['suspense']);

        $reversal = $journal->reverse(
            $entry,
            'reversal:provider-ref-002',
            'Transaction annulée par le fournisseur.'
        );
        $after = $positions->summary();

        $this->assertSame(JournalEntryType::REVERSAL, $reversal->type);
        $this->assertSame(JournalEntryStatus::REVERSED, $entry->fresh()->status);
        $this->assertSame($reversal->id, $entry->fresh()->reversed_entry_id);
        $this->assertSame(0, $after['cash_clearing']);
        $this->assertSame(0, $after['suspense']);
        $this->assertDatabaseCount('financial_journal_entries', 2);
    }
}
