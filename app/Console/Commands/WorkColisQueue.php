<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WorkColisQueue extends Command
{
    protected $signature = 'worker:colis
                            {--sleep=2}
                            {--tries=3}
                            {--timeout=120}
                            {--stop-when-empty}
                            {--once}';

    protected $description = 'Consomme la file colis sur la connexion configuree';

    public function handle()
    {
        $connection = config('module_queues.modules.colis.connection', 'database');
        $queue = config('module_queues.modules.colis.queue', 'colis');

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

        return $this->call('queue:work', $parameters);
    }
}
