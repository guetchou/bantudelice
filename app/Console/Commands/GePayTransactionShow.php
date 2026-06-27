<?php

namespace App\Console\Commands;

use App\Domain\GePay\Models\GePayTransaction;
use Illuminate\Console\Command;

class GePayTransactionShow extends Command
{
    protected $signature = 'gepay:transaction-show {reference}';
    protected $description = "Afficher les détails d'une transaction GePay par UUID ou référence externe.";

    public function handle(): int
    {
        $ref = (string) $this->argument('reference');

        $transaction = GePayTransaction::query()
            ->where('uuid', $ref)
            ->orWhere('provider_reference', $ref)
            ->orWhere('external_reference', $ref)
            ->first();

        if (! $transaction) {
            $this->error('Transaction GePay introuvable.');
            return self::FAILURE;
        }

        $this->table(['Champ', 'Valeur'], [
            ['UUID', $transaction->uuid],
            ['Client ID', $transaction->client_id],
            ['Type', $transaction->type->value],
            ['Provider', $transaction->provider],
            ['Statut', $transaction->status->value],
            ['Montant', number_format((int) $transaction->amount, 0, ',', ' ').' '.$transaction->currency],
            ['Téléphone masqué', $transaction->phone_masked],
            ['Référence externe', $transaction->external_reference],
            ['Référence provider', $transaction->provider_reference ?? '—'],
            ['Code échec', $transaction->failure_code ?? '—'],
            ['Message échec', $transaction->failure_message ?? '—'],
            ['Soumis à', $transaction->submitted_at?->toIso8601String() ?? '—'],
            ['Complété à', $transaction->completed_at?->toIso8601String() ?? '—'],
            ['Dernier contrôle', $transaction->last_checked_at?->toIso8601String() ?? '—'],
            ['Créé à', $transaction->created_at?->toIso8601String() ?? '—'],
        ]);

        return self::SUCCESS;
    }
}
