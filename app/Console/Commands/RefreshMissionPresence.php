<?php

namespace App\Console\Commands;

use App\Services\MissionPresenceRefreshService;
use Illuminate\Console\Command;

class RefreshMissionPresence extends Command
{
    protected $signature = 'missions:refresh-presence';

    protected $description = 'Rafraichit la presence temps reel des missions food, transport et colis';

    public function handle(MissionPresenceRefreshService $service): int
    {
        $result = $service->refreshActiveMissions();

        $this->info('Food: ' . $result['food']);
        $this->info('Transport: ' . $result['transport']);
        $this->info('Colis: ' . $result['colis']);
        $this->info('Total: ' . $result['total']);

        return self::SUCCESS;
    }
}
