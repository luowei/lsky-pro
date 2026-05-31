<?php

namespace App\AssetRouter\Services;

use App\Models\AssetRouter\AssetRouterAsset;
use App\Models\AssetRouter\AssetRouterJob;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class SecondBrainAssetSyncService
{
    public function enabled(): bool
    {
        return (bool) config('asset-router.second_brain.sync_url');
    }

    public function queue(AssetRouterAsset $asset, string $event): void
    {
        if (! $this->enabled()) {
            return;
        }

        AssetRouterJob::query()->create([
            'asset_id' => $asset->id,
            'type' => 'sync_second_brain_metadata',
            'status' => 'queued',
            'payload' => [
                'event' => $event,
                'asset_id' => $asset->id,
                'key' => $asset->key,
            ],
        ]);
    }

    public function process(AssetRouterJob $job): AssetRouterJob
    {
        if ($job->type !== 'sync_second_brain_metadata') {
            return $job;
        }

        $job->loadMissing('asset.providerObjects');

        if (! $job->asset) {
            return $this->fail($job, 'Asset no longer exists.');
        }

        try {
            $job->forceFill([
                'status' => 'running',
                'attempts' => $job->attempts + 1,
                'last_error' => null,
            ])->save();

            $response = Http::withToken((string) config('asset-router.second_brain.sync_token'))
                ->acceptJson()
                ->timeout(30)
                ->post((string) config('asset-router.second_brain.sync_url'), [
                    'event' => $job->payload?->get('event', 'asset.changed') ?: 'asset.changed',
                    'asset' => $job->asset->toArray(),
                    'provider_objects' => $job->asset->providerObjects->toArray(),
                    'links' => $job->asset->links,
                ]);

            if (! $response->successful()) {
                throw new RuntimeException("second-brain sync failed: {$response->status()} {$response->body()}");
            }

            $job->forceFill([
                'status' => 'succeeded',
                'result' => [
                    'status' => $response->status(),
                ],
                'last_error' => null,
            ])->save();
        } catch (Throwable $e) {
            return $this->fail($job, $e->getMessage());
        }

        return $job->fresh(['asset']);
    }

    private function fail(AssetRouterJob $job, string $message): AssetRouterJob
    {
        $job->forceFill([
            'status' => 'failed',
            'last_error' => $message,
        ])->save();

        return $job->fresh(['asset']);
    }
}
