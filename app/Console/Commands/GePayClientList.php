<?php

namespace App\Console\Commands;

use App\Domain\GePay\Models\GePayClient;
use Illuminate\Console\Command;

class GePayClientList extends Command
{
    protected $signature = 'gepay:client-list {--inactive}';
    protected $description = 'Lister les clients API GePay.';

    public function handle(): int
    {
        $query = GePayClient::query()->orderBy('id');
        if (! $this->option('inactive')) {
            $query->where('is_active', true);
        }

        $clients = $query->get(['uuid', 'name', 'api_key', 'capabilities', 'is_active', 'created_at']);

        if ($clients->isEmpty()) {
            $this->info('Aucun client GePay.');
            return self::SUCCESS;
        }

        $this->table(
            ['UUID', 'Nom', 'API Key', 'Capacités', 'Actif', 'Créé'],
            $clients->map(fn ($c) => [
                $c->uuid,
                $c->name,
                $c->api_key,
                implode(', ', $c->capabilities ?? []),
                $c->is_active ? 'oui' : 'non',
                $c->created_at?->toDateString(),
            ])->toArray()
        );

        return self::SUCCESS;
    }
}
