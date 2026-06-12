<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        require_once database_path('seeders/CmsCoreSeeder.php');

        $this->call(\Database\Seeds\SystemSettingsSeeder::class);
        $this->call(\Database\Seeders\CmsCoreSeeder::class);

        if ($this->shouldSeedLegacyDemoData()) {
            $this->call(CongoTestDataSeeder::class);
        } else {
            $this->command?->info('CongoTestDataSeeder ignoré: données de démonstration déjà présentes.');
        }
    }

    private function shouldSeedLegacyDemoData(): bool
    {
        if (!Schema::hasTable('users')) {
            return false;
        }

        return !DB::table('users')
            ->where('email', 'client@bantudelice.cg')
            ->exists();
    }
}
