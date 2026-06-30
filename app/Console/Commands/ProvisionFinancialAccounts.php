<?php

namespace App\Console\Commands;

use App\Domain\Finance\Services\FinancialAccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ProvisionFinancialAccounts extends Command
{
    protected $signature = 'finance:provision-accounts
        {--commit : Créer réellement les comptes. Sans cette option, la commande reste en simulation.}';

    protected $description = 'Provisionne les comptes BantuDelice, restaurants et livreurs sans créer de solde historique.';

    public function handle(FinancialAccountService $accounts): int
    {
        if (! Schema::hasTable('financial_accounts')) {
            $this->error('La migration du registre financier n’a pas encore été exécutée.');
            return self::FAILURE;
        }

        $commit = (bool) $this->option('commit');
        $restaurantCount = Schema::hasTable('restaurants') ? DB::table('restaurants')->count() : 0;
        $driverCount = Schema::hasTable('drivers') ? DB::table('drivers')->count() : 0;

        $this->table(
            ['Périmètre', 'Comptes attendus'],
            [
                ['Plateforme BantuDelice', 8],
                ['Restaurants', $restaurantCount * 2],
                ['Livreurs', $driverCount * 2],
            ]
        );

        if (! $commit) {
            $this->warn('Simulation uniquement. Relancez avec --commit pour créer les comptes.');
            $this->warn('Aucun solde d’ouverture n’est créé automatiquement : il doit être rapproché et approuvé.');
            return self::SUCCESS;
        }

        $accounts->provisionPlatform();

        if (Schema::hasTable('restaurants')) {
            DB::table('restaurants')
                ->select('id')
                ->orderBy('id')
                ->chunkById(200, function ($restaurants) use ($accounts): void {
                    foreach ($restaurants as $restaurant) {
                        $accounts->provisionPartner('restaurant', (int) $restaurant->id);
                    }
                });
        }

        if (Schema::hasTable('drivers')) {
            DB::table('drivers')
                ->select('id')
                ->orderBy('id')
                ->chunkById(200, function ($drivers) use ($accounts): void {
                    foreach ($drivers as $driver) {
                        $accounts->provisionPartner('driver', (int) $driver->id);
                    }
                });
        }

        $this->info('Comptes financiers provisionnés. Aucun solde historique n’a été inventé.');
        $this->line('Étape suivante : rapprocher les obligations historiques avant toute bascule des dashboards.');

        return self::SUCCESS;
    }
}
