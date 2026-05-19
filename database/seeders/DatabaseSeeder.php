<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SystemSettingsSeeder::class);
        $this->call(CmsCoreSeeder::class);

        if ($this->shouldSeedLegacyDemoData()) {
            $this->call(\CongoTestDataSeeder::class);
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
