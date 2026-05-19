<?php

namespace App\Console\Commands;

use App\CmsContent;
use Illuminate\Console\Command;

class PublishScheduledCmsContent extends Command
{
    protected $signature = 'cms:publish-scheduled';
    protected $description = 'Verifie les contenus CMS publies a une date planifiee';

    public function handle(): int
    {
        $count = CmsContent::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->count();

        $this->info("Contenus CMS publics disponibles: {$count}");

        return self::SUCCESS;
    }
}
