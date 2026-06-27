<?php

namespace App\Console\Commands;

use App\Domain\GePay\Models\GePayClient;
use Illuminate\Console\Command;

class GePayClientDisable extends Command
{
    protected $signature = 'gepay:client-disable {uuid}';
    protected $description = 'Désactiver un client API GePay.';

    public function handle(): int
    {
        $client = GePayClient::query()->where('uuid', $this->argument('uuid'))->first();
        if (! $client) {
            $this->error('Client GePay introuvable.');
            return self::FAILURE;
        }

        if (! $client->is_active) {
            $this->warn("Le client \"{$client->name}\" est déjà désactivé.");
            return self::SUCCESS;
        }

        if (! $this->confirm("Désactiver le client \"{$client->name}\" ({$client->api_key}) ?")) {
            $this->info('Annulé.');
            return self::SUCCESS;
        }

        $client->forceFill(['is_active' => false])->save();
        $this->info("Client \"{$client->name}\" désactivé. Les tokens existants seront rejetés immédiatement.");

        return self::SUCCESS;
    }
}
