<?php

namespace App\Console\Commands;

use App\CmsContent;
use App\Services\CmsContentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishScheduledCmsContent extends Command
{
    protected $signature = 'cms:publish-scheduled';
    protected $description = 'Publie les contenus CMS dont la date de publication est atteinte';

    public function handle(CmsContentService $cms): int
    {
        $scheduled = CmsContent::query()
            ->whereIn('status', ['draft', 'pending_review'])
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get();

        if ($scheduled->isEmpty()) {
            return self::SUCCESS;
        }

        $published = 0;
        $failed = 0;

        foreach ($scheduled as $content) {
            try {
                $cms->transition($content, 'published', null, 'Publication automatique planifiée');
                $published++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('CMS: échec publication planifiée', [
                    'content_id' => $content->id,
                    'title' => $content->title,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("CMS publish-scheduled: {$published} publiés, {$failed} échecs.");
        Log::info('CMS publish-scheduled exécuté', ['published' => $published, 'failed' => $failed]);

        return self::SUCCESS;
    }
}
