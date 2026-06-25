<?php

namespace App\Console\Commands;

use App\Order;
use App\Services\FoodOrderHeaderProjector;
use Illuminate\Console\Command;

class ProjectFoodOrderHeaders extends Command
{
    protected $signature = 'food:project-order-headers
        {--all : Reprojette tout l’historique}
        {--minutes=15 : Fenêtre des commandes récemment modifiées}';

    protected $description = 'Projette les groupes order_no dans la table des entêtes';

    public function handle(FoodOrderHeaderProjector $projector): int
    {
        $query = Order::query()->whereNotNull('order_no');

        if (! $this->option('all')) {
            $minutes = max(1, (int) $this->option('minutes'));
            $query->where('updated_at', '>=', now()->subMinutes($minutes));
        }

        $numbers = $query->distinct()->pluck('order_no')->filter();

        $numbers->each(function ($number) use ($projector) {
            $projector->project((string) $number);
        });

        $this->info('Entêtes projetés : ' . $numbers->count());
        return self::SUCCESS;
    }
}
