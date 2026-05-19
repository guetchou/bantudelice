<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WorkFoodQueue extends Command
{
    protected $signature = 'worker:food
                            {--sleep=1}
                            {--tries=3}
                            {--timeout=120}
                            {--stop-when-empty}
                            {--once}';

    protected $description = 'Consomme la file food sur la connexion configuree';

    public function handle()
    {
        $connection = config('module_queues.modules.food.connection', 'database_food');
        $queue = config('module_queues.modules.food.queue', 'food');

        $parameters = [
            'connection' => $connection,
            '--queue' => $queue,
            '--sleep' => (int) $this->option('sleep'),
            '--tries' => (int) $this->option('tries'),
            '--timeout' => (int) $this->option('timeout'),
        ];

        if ($this->option('stop-when-empty')) {
            $parameters['--stop-when-empty'] = true;
        }

        if ($this->option('once')) {
            $parameters['--once'] = true;
        }

        $this->info("Demarrage worker-food sur {$connection}:{$queue}");

        return $this->call('queue:work', $parameters);
    }
}
