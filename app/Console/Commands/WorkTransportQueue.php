<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WorkTransportQueue extends Command
{
    protected $signature = 'worker:transport
                            {--sleep=1}
                            {--tries=5}
                            {--timeout=180}
                            {--stop-when-empty}
                            {--once}';

    protected $description = 'Consomme la file transport sur la connexion configuree';

    public function handle()
    {
        $connection = config('module_queues.modules.transport.connection', 'database');
        $queue = config('module_queues.modules.transport.queue', 'transport');

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
