<?php

namespace App\AssetRouter\Services;

use App\AssetRouter\Enums\AssetRouterProvider;
use App\AssetRouter\Enums\AssetRouterVisibility;
use App\Models\AssetRouter\AssetRouterAsset;
use App\Models\AssetRouter\AssetRouterProviderObject;
use Illuminate\Support\Facades\Http;
use Throwable;

class AssetRouterProviderStatusService
{
    public function __construct(
        private AssetRouterStorage $storage,
        private AssetUrlBuilder $urlBuilder,
    ) {
    }

    public function summary(): array
    {
        $counts = AssetRouterProviderObject::query()
            ->selectRaw('provider, status, count(*) as total')
            ->groupBy('provider', 'status')
            ->get()
            ->groupBy('provider')
            ->map(fn ($rows) => $rows->pluck('total', 'status'));

        return [
            [
                'provider' => AssetRouterProvider::R2,
                'enabled' => (bool) config('asset-router.r2.enabled'),
                'role' => 'primary',
                'bucket' => config('asset-router.r2.bucket'),
                'status_counts' => $counts->get(AssetRouterProvider::R2, collect()),
            ],
            [
                'provider' => AssetRouterProvider::GithubJsdelivr,
                'enabled' => (bool) config('asset-router.github.repo'),
                'role' => 'public_mirror',
                'repo' => config('asset-router.github.repo'),
                'branch' => config('asset-router.github.branch'),
                'base_url' => config('asset-router.github.jsdelivr_base_url'),
                'status_counts' => $counts->get(AssetRouterProvider::GithubJsdelivr, collect()),
            ],
            [
                'provider' => AssetRouterProvider::Lsky,
                'enabled' => (bool) config('asset-router.lsky.base_url'),
                'role' => 'legacy_fallback',
                'base_url' => config('asset-router.lsky.base_url'),
                'status_counts' => $counts->get(AssetRouterProvider::Lsky, collect()),
            ],
        ];
    }

    public function probe(AssetRouterAsset $asset): array
    {
        $results = [
            $this->probePrimary($asset),
        ];

        if (AssetRouterVisibility::isPublic($asset->visibility)) {
            $results[] = $this->probeJsdelivr($asset);
        }

        return $results;
    }

    private function probePrimary(AssetRouterAsset $asset): array
    {
        try {
            $this->storage->get($asset->key);
            $status = 'present';
            $error = null;
        } catch (Throwable $e) {
            $status = 'missing';
            $error = $e->getMessage();
        }

        AssetRouterProviderObject::query()->updateOrCreate([
            'asset_id' => $asset->id,
            'provider' => config('asset-router.r2.enabled') ? AssetRouterProvider::R2 : AssetRouterProvider::Local,
        ], [
            'provider_key' => $asset->key,
            'url' => $asset->url,
            'status' => $status,
            'last_checked_at' => now(),
            'last_error' => $error,
        ]);

        return [
            'provider' => config('asset-router.r2.enabled') ? AssetRouterProvider::R2 : AssetRouterProvider::Local,
            'status' => $status,
            'url' => $asset->url,
            'last_error' => $error,
        ];
    }

    private function probeJsdelivr(AssetRouterAsset $asset): array
    {
        $url = $this->urlBuilder->jsdelivrUrl($asset->key);
        $status = 'missing';
        $error = null;

        try {
            $response = Http::timeout(15)->head($url);
            $status = $response->successful() ? 'present' : 'missing';
            $error = $response->successful() ? null : "HTTP {$response->status()}";
        } catch (Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
        }

        AssetRouterProviderObject::query()->updateOrCreate([
            'asset_id' => $asset->id,
            'provider' => AssetRouterProvider::GithubJsdelivr,
        ], [
            'provider_key' => $asset->key,
            'url' => $url,
            'status' => $status,
            'last_checked_at' => now(),
            'last_error' => $error,
        ]);

        return [
            'provider' => AssetRouterProvider::GithubJsdelivr,
            'status' => $status,
            'url' => $url,
            'last_error' => $error,
        ];
    }
}
