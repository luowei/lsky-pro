<?php

namespace App\Jobs\AssetRouter;

use App\AssetRouter\Services\AssetRouterMirrorService;
use App\Models\AssetRouter\AssetRouterJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MirrorPublicAssetToGithub implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public string $assetRouterJobId)
    {
        $this->onQueue(config('asset-router.mirror.queue'));
    }

    public function handle(AssetRouterMirrorService $service): void
    {
        $job = AssetRouterJob::query()->find($this->assetRouterJobId);

        if (! $job || ! in_array($job->status, ['queued', 'failed'], true)) {
            return;
        }

        $service->process($job);
    }
}
