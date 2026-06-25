<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ApplyFoodIntegrityConstraintsTest extends TestCase
{
    private function commandSource(): string
    {
        $path = dirname(__DIR__, 2) . '/app/Console/Commands/ApplyFoodIntegrityConstraints.php';
        $contents = file_get_contents($path);
        self::assertNotFalse($contents);
        return $contents;
    }

    public function test_command_checks_duplicates_before_schema_changes(): void
    {
        $source = $this->commandSource();
        self::assertStringContainsString('duplicatePayments', $source);
        self::assertStringContainsString('duplicateDeliveries', $source);
        self::assertStringContainsString('dry-run', $source);
        self::assertStringContainsString('force', $source);
    }

    public function test_expected_unique_indexes_are_declared(): void
    {
        $source = $this->commandSource();
        self::assertStringContainsString('payments_order_provider_unique', $source);
        self::assertStringContainsString('deliveries_order_unique', $source);
        self::assertStringContainsString("['order_id', 'provider']", $source);
    }

    public function test_existing_indexes_are_detected(): void
    {
        $source = $this->commandSource();
        self::assertStringContainsString('getDoctrineSchemaManager', $source);
        self::assertStringContainsString('listTableIndexes', $source);
    }
}
