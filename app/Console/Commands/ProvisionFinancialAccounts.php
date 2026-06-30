<?php

namespace App\Console\Commands;

use App\Domain\Finance\Services\FinancialAccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ProvisionFinancialAccounts extends Command
{
    protected $signature = 'finance:provision-accounts {--commit : Create accounts instead of running a dry-run.}';

    protected $description = 'Provision BantuDelice, restaurant and driver financial accounts without opening balances.';

    public function handle(FinancialAccountService $accounts): int
    {
        if (! Schema::hasTable('financial_accounts')) {
            $this->error('Financial ledger migration is not installed.');
            return self::FAILURE;
        }

        $commit = (bool) $this->option('commit');
        $restaurantCount = Schema::hasTable('restaurants') ? DB::table('restaurants')->count() : 0;
        $driverCount = Schema::hasTable('drivers') ? DB::table('drivers')->count() : 0;

        $this->table(
            ['Scope', 'Expected accounts'],
            [
                ['BantuDelice platform', 10],
                ['Restaurants', $restaurantCount * 3],
                ['Drivers', $driverCount * 3],
            ]
        );

        if (! $commit) {
            $this->warn('Dry-run only. Use --commit to create accounts.');
            $this->warn('No opening balance is created automatically.');
            return self::SUCCESS;
        }

        $accounts->provisionPlatform();

        if (Schema::hasTable('restaurants')) {
            DB::table('restaurants')->select('id')->orderBy('id')->chunkById(200, function ($rows) use ($accounts): void {
                foreach ($rows as $row) {
                    $accounts->provisionPartner('restaurant', (int) $row->id);
                }
            });
        }

        if (Schema::hasTable('drivers')) {
            DB::table('drivers')->select('id')->orderBy('id')->chunkById(200, function ($rows) use ($accounts): void {
                foreach ($rows as $row) {
                    $accounts->provisionPartner('driver', (int) $row->id);
                }
            });
        }

        $this->info('Financial accounts provisioned without opening balances.');
        return self::SUCCESS;
    }
}
