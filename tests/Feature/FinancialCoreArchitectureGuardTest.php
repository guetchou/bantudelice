<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

final class FinancialCoreArchitectureGuardTest extends TestCase
{
    private string $temporaryPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryPath = storage_path('framework/testing/finance-core-' . Str::uuid());
        File::ensureDirectoryExists($this->temporaryPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->temporaryPath);
        parent::tearDown();
    }

    public function test_guard_accepts_one_creator_per_financial_table(): void
    {
        File::put(
            $this->temporaryPath . '/2026_01_01_create_finance.php',
            "<?php Schema::create('financial_accounts', function () {});"
        );

        $this->artisan('finance:audit-core-architecture', ['--path' => $this->temporaryPath])
            ->expectsOutputToContain('No competing financial table creation detected.')
            ->assertExitCode(0);
    }

    public function test_guard_rejects_two_migrations_creating_the_same_financial_table(): void
    {
        File::put(
            $this->temporaryPath . '/2026_01_01_create_finance_a.php',
            "<?php Schema::create('financial_accounts', function () {});"
        );
        File::put(
            $this->temporaryPath . '/2026_01_02_create_finance_b.php',
            "<?php Schema::create('financial_accounts', function () {});"
        );

        $this->artisan('finance:audit-core-architecture', ['--path' => $this->temporaryPath])
            ->expectsOutputToContain('Do not merge or deploy competing financial cores together.')
            ->assertExitCode(1);
    }

    public function test_guard_rejects_an_unknown_configured_engine(): void
    {
        config()->set('financial-core.engine', 'unknown-engine');

        $this->artisan('finance:audit-core-architecture', ['--path' => $this->temporaryPath])
            ->expectsOutputToContain('Unknown FINANCIAL_CORE_ENGINE value')
            ->assertExitCode(1);
    }
}
