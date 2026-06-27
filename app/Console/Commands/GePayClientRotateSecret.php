<?php

namespace App\Console\Commands;

use App\Domain\GePay\Models\GePayClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GePayClientRotateSecret extends Command
{
    protected $signature = 'gepay:client-rotate-secret {uuid}';
    protected $description = "Générer un nouveau secret pour un client GePay. L'ancien secret est immédiatement invalidé.";

    public function handle(): int
    {
        $client = GePayClient::query()->where('uuid', $this->argument('uuid'))->first();
        if (! $client) {
            $this->error('Client GePay introuvable.');
            return self::FAILURE;
        }

        if (! $this->confirm("Rotation du secret du client \"{$client->name}\" ({$client->api_key}) ? L'ancien secret devient invalide immediatement.")) {
            $this->info('Annulé.');
            return self::SUCCESS;
        }

        $newSecret = Str::random(64);
        $client->forceFill(['api_secret' => $newSecret])->save();

        $this->warn('Conservez le nouveau secret maintenant : il ne sera plus affiché.');
        $this->table(['Champ', 'Valeur'], [
            ['Client UUID', $client->uuid],
            ['API Key', $client->api_key],
            ['Nouveau secret', $newSecret],
        ]);

        return self::SUCCESS;
    }
}
