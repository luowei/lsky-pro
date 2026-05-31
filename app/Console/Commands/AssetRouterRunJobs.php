<?php

namespace App\Console\Commands;

use App\AssetRouter\Services\AssetRouterMirrorService;
use App\Models\AssetRouter\AssetRouterJob;
use Illuminate\Console\Command;

class AssetRouterRunJobs extends Command
{
    protected $signature = 'asset-router:run-jobs {--limit=20 : Maximum jobs to process}';

    protected $description = 'Process queued Asset Router control-plane jobs.';

    public function handle(AssetRouterMirrorService $service): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $jobs = AssetRouterJob::query()
            ->where('type', 'mirror_public_to_github')
            ->whereIn('status', ['queued', 'failed'])
            ->oldest()
            ->limit($limit)
            ->get();

        foreach ($jobs as $job) {
            $result = $service->process($job);
            $this->line("{$result->id} {$result->status}");
        }

        $this->info("Processed {$jobs->count()} Asset Router job(s).");

        return self::SUCCESS;
    }
}
