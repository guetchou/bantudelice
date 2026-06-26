<?php

namespace App\Console\Commands;

use App\Domain\GePay\Models\GePayClient;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GePayCreateClient extends Command
{
    protected $signature = 'gepay:client-create {name} {--capability=*} {--ip=*} {--webhook-url=}';
    protected $description = 'Créer un client API GePay et afficher son secret une seule fois.';

    public function handle(): int
    {
        $secret = Str::random(64);
        $client = GePayClient::create([
            'uuid' => (string) Str::uuid(),
            'name' => (string) $this->argument('name'),
            'api_key' => 'gpk_'.Str::lower(Str::random(32)),
            'api_secret' => $secret,
            'capabilities' => $this->option('capability') ?: ['collection'],
            'allowed_ips' => $this->option('ip') ?: [],
            'webhook_url' => $this->option('webhook-url'),
            'webhook_secret' => Str::random(64),
            'is_active' => true,
        ]);

        $this->warn('Conservez le secret maintenant : il ne sera plus affiché.');
        $this->table(['Champ', 'Valeur'], [
            ['Client UUID', $client->uuid],
            ['API key', $client->api_key],
            ['API secret', $secret],
            ['Capacités', implode(', ', $client->capabilities ?? [])],
        ]);

        return self::SUCCESS;
    }
}
