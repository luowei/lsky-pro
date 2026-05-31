<?php

namespace App\AssetRouter\Services;

use App\AssetRouter\Clients\GitHubAssetClient;
use App\AssetRouter\Enums\AssetRouterProvider;
use App\Models\AssetRouter\AssetRouterJob;
use App\Models\AssetRouter\AssetRouterProviderObject;
use Illuminate\Support\Facades\DB;
use Throwable;

class AssetRouterMirrorService
{
    public function __construct(
        private AssetRouterStorage $storage,
        private AssetUrlBuilder $urlBuilder,
        private GitHubAssetClient $github,
    ) {
    }

    public function process(AssetRouterJob $job): AssetRouterJob
    {
        if ($job->type !== 'mirror_public_to_github') {
            return $job;
        }

        $job->loadMissing('asset');

        if (! $job->asset) {
            return $this->fail($job, 'Asset no longer exists.');
        }

        try {
            $job->forceFill([
                'status' => 'running',
                'attempts' => $job->attempts + 1,
                'last_error' => null,
            ])->save();

            $contents = $this->storage->get($job->asset->key);
            $result = $this->github->putObject($job->asset->key, $contents, $job->asset->mime_type);
            $jsdelivrUrl = $this->urlBuilder->jsdelivrUrl($job->asset->key);

            DB::transaction(function () use ($job, $result, $jsdelivrUrl) {
                AssetRouterProviderObject::query()->updateOrCreate([
                    'asset_id' => $job->asset_id,
                    'provider' => AssetRouterProvider::GithubJsdelivr,
                ], [
                    'provider_key' => $result['provider_key'],
                    'url' => $jsdelivrUrl,
                    'status' => 'present',
                    'etag' => $result['content_sha'] ?? null,
                    'last_error' => null,
                    'last_checked_at' => now(),
                ]);

                $job->asset->forceFill([
                    'status' => 'active',
                ])->save();

                $job->forceFill([
                    'status' => 'succeeded',
                    'result' => [
                        'provider' => AssetRouterProvider::GithubJsdelivr,
                        'url' => $jsdelivrUrl,
                        'commit_sha' => $result['commit_sha'] ?? null,
                        'content_sha' => $result['content_sha'] ?? null,
                    ],
                    'last_error' => null,
                ])->save();
            });
        } catch (Throwable $e) {
            return $this->fail($job, $e->getMessage());
        }

        return $job->fresh(['asset', 'asset.providerObjects']);
    }

    private function fail(AssetRouterJob $job, string $message): AssetRouterJob
    {
        DB::transaction(function () use ($job, $message) {
            if ($job->asset) {
                $job->asset->forceFill([
                    'status' => 'mirror_failed',
                ])->save();
            }

            $job->forceFill([
                'status' => 'failed',
                'last_error' => $message,
            ])->save();
        });

        return $job->fresh(['asset']);
    }
}
