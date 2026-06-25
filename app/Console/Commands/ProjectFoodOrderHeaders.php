<?php

namespace App\Console\Commands;

use App\Order;
use App\Services\FoodOrderHeaderProjector;
use Illuminate\Console\Command;

class ProjectFoodOrderHeaders extends Command
{
    protected $signature = 'food:project-order-headers';
    protected $description = 'Projette les commandes historiques dans la table des entêtes';

    public function handle(FoodOrderHeaderProjector $projector): int
    {
        $numbers = Order::query()->distinct()->pluck('order_no')->filter();

        $numbers->each(function ($number) use ($projector) {
            $projector->project((string) $number);
        });

        $this->info('Entêtes projetés : ' . $numbers->count());
        return self::SUCCESS;
    }
}
