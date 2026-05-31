<?php

namespace App\Console\Commands;

use App\AssetRouter\Enums\AssetRouterProvider;
use App\AssetRouter\Enums\AssetRouterVisibility;
use App\AssetRouter\Services\AssetUrlBuilder;
use App\Models\AssetRouter\AssetRouterAsset;
use App\Models\AssetRouter\AssetRouterProviderObject;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AssetRouterImportProviders extends Command
{
    protected $signature = 'asset-router:import-providers
        {--source=all : all, r2, or github}
        {--prefix= : Optional key prefix}
        {--limit=0 : Maximum provider objects to import per source}
        {--per-page=1000 : R2 page size}';

    protected $description = 'Import existing R2 and GitHub/jsDelivr asset metadata into the Asset Router control plane.';

    private array $githubPublicKeys = [];

    public function handle(AssetUrlBuilder $urlBuilder): int
    {
        $source = (string) $this->option('source');
        $limit = max(0, (int) $this->option('limit'));
        $prefix = (string) ($this->option('prefix') ?: '');
        $imported = [
            AssetRouterProvider::GithubJsdelivr => 0,
            AssetRouterProvider::R2 => 0,
        ];

        if (in_array($source, ['all', 'github'], true)) {
            $githubItems = $this->fetchGithubTree($prefix, $limit);
            foreach ($githubItems as $item) {
                $this->githubPublicKeys[$item['path']] = true;
                $this->githubPublicKeys[$this->stripLeadingImagePrefix($item['path'])] = true;
                if ($this->importGithubObject($item, $urlBuilder)) {
                    $imported[AssetRouterProvider::GithubJsdelivr]++;
                }
            }
        }

        if (in_array($source, ['all', 'r2'], true)) {
            if ($source === 'r2') {
                $this->githubPublicKeys = $this->fetchGithubPublicKeySet($prefix);
            }

            foreach ($this->fetchR2Objects($prefix, $limit) as $item) {
                if ($this->importR2Object($item, $urlBuilder)) {
                    $imported[AssetRouterProvider::R2]++;
                }
            }
        }

        $this->info("Imported {$imported[AssetRouterProvider::R2]} R2 object(s), {$imported[AssetRouterProvider::GithubJsdelivr]} GitHub object(s).");

        return self::SUCCESS;
    }

    private function fetchGithubTree(string $prefix, int $limit): array
    {
        $repo = (string) config('asset-router.github.repo');
        $branch = (string) config('asset-router.github.branch', 'main');
        $url = "https://api.github.com/repos/{$repo}/git/trees/{$branch}?recursive=1";
        $request = Http::acceptJson()->withHeaders(['User-Agent' => 'lsky-pro-asset-router']);

        if (config('asset-router.github.token')) {
            $request = $request->withToken((string) config('asset-router.github.token'));
        }

        $response = $request->timeout(60)->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("GitHub tree import failed: {$response->status()} {$response->body()}");
        }

        $items = collect($response->json('tree') ?: [])
            ->filter(fn ($item) => ($item['type'] ?? '') === 'blob')
            ->filter(fn ($item) => $this->isAssetKey((string) ($item['path'] ?? '')))
            ->filter(function ($item) use ($prefix) {
                $path = (string) $item['path'];

                return ! $prefix
                    || str_starts_with($path, $prefix)
                    || str_starts_with($this->normalizeGithubKey($path), $prefix);
            })
            ->values();

        if ($limit > 0) {
            $items = $items->take($limit);
        }

        return $items->all();
    }

    private function fetchGithubPublicKeySet(string $prefix): array
    {
        $keys = [];

        foreach ($this->fetchGithubTree($prefix, 0) as $item) {
            $path = (string) $item['path'];
            $keys[$path] = true;
            $keys[$this->stripLeadingImagePrefix($path)] = true;
        }

        return $keys;
    }

    private function fetchR2Objects(string $prefix, int $limit): iterable
    {
        $cursor = null;
        $seen = 0;
        $perPage = max(1, min(1000, (int) $this->option('per-page')));

        do {
            $query = [
                'per_page' => $perPage,
            ];
            if ($cursor) {
                $query['cursor'] = $cursor;
            }
            if ($prefix) {
                $query['prefix'] = $prefix;
            }

            $response = Http::withToken((string) config('asset-router.r2.api_token'))
                ->acceptJson()
                ->timeout(60)
                ->get(sprintf(
                    'https://api.cloudflare.com/client/v4/accounts/%s/r2/buckets/%s/objects',
                    rawurlencode((string) config('asset-router.r2.account_id')),
                    rawurlencode((string) config('asset-router.r2.bucket'))
                ), $query);

            if (! $response->successful()) {
                throw new RuntimeException("R2 object import failed: {$response->status()} {$response->body()}");
            }

            foreach ($response->json('result') ?: [] as $item) {
                $key = (string) ($item['key'] ?? '');
                if (! $this->isAssetKey($key)) {
                    continue;
                }
                yield $item;
                $seen++;
                if ($limit > 0 && $seen >= $limit) {
                    return;
                }
            }

            $cursor = $response->json('result_info.cursor');
        } while ($cursor && $response->json('result_info.is_truncated'));
    }

    private function importGithubObject(array $item, AssetUrlBuilder $urlBuilder): bool
    {
        $path = (string) $item['path'];
        $key = $this->normalizeGithubKey($path);

        DB::transaction(function () use ($item, $key, $path, $urlBuilder) {
            $asset = $this->upsertAsset([
                'key' => $key,
                'size_bytes' => (int) ($item['size'] ?? 0),
                'mime_type' => $this->guessMimeType($key),
                'visibility' => AssetRouterVisibility::Public,
                'primary_provider' => AssetRouterProvider::GithubJsdelivr,
                'url_builder' => $urlBuilder,
            ]);

            AssetRouterProviderObject::query()->updateOrCreate([
                'asset_id' => $asset->id,
                'provider' => AssetRouterProvider::GithubJsdelivr,
            ], [
                'provider_key' => $path,
                'url' => $urlBuilder->jsdelivrUrl($path),
                'status' => 'present',
                'etag' => $item['sha'] ?? null,
                'last_checked_at' => now(),
                'last_error' => null,
            ]);
        });

        return true;
    }

    private function importR2Object(array $item, AssetUrlBuilder $urlBuilder): bool
    {
        $key = (string) $item['key'];
        $public = isset($this->githubPublicKeys[$key]) || isset($this->githubPublicKeys[$this->stripLeadingImagePrefix($key)]);

        DB::transaction(function () use ($item, $key, $public, $urlBuilder) {
            $asset = $this->upsertAsset([
                'key' => $key,
                'size_bytes' => (int) ($item['size'] ?? 0),
                'mime_type' => $this->r2ContentType($item, $key),
                'visibility' => $public ? AssetRouterVisibility::Public : AssetRouterVisibility::Members,
                'primary_provider' => AssetRouterProvider::R2,
                'url_builder' => $urlBuilder,
            ]);

            AssetRouterProviderObject::query()->updateOrCreate([
                'asset_id' => $asset->id,
                'provider' => AssetRouterProvider::R2,
            ], [
                'provider_key' => $key,
                'url' => $urlBuilder->canonicalUrl($key),
                'status' => 'present',
                'etag' => $item['etag'] ?? null,
                'last_checked_at' => $this->lastModifiedAt($item) ?: now(),
                'last_error' => null,
            ]);
        });

        return true;
    }

    private function upsertAsset(array $data): AssetRouterAsset
    {
        /** @var AssetUrlBuilder $urlBuilder */
        $urlBuilder = $data['url_builder'];
        $key = $data['key'];
        $extension = strtolower(pathinfo($key, PATHINFO_EXTENSION));
        $asset = AssetRouterAsset::query()->firstOrNew(['key' => $key]);
        $visibility = $asset->exists && $asset->visibility === AssetRouterVisibility::Public
            ? AssetRouterVisibility::Public
            : $data['visibility'];

        $asset->forceFill([
            'owner_user_id' => $asset->owner_user_id,
            'display_name' => $asset->display_name ?: basename($key),
            'original_name' => $asset->original_name ?: basename($key),
                'mime_type' => $asset->mime_type ?: $data['mime_type'],
            'extension' => $asset->extension ?: $extension,
            'size_bytes' => max((int) $asset->size_bytes, (int) $data['size_bytes']),
            'visibility' => $visibility,
                'asset_type' => str_starts_with((string) $data['mime_type'], 'image/') ? 'image' : 'file',
            'status' => 'active',
            'canonical_url' => $urlBuilder->canonicalUrl($key),
            'members_url' => $urlBuilder->membersUrl($key),
            'primary_provider' => $asset->primary_provider ?: $data['primary_provider'],
            'metadata' => $asset->metadata ?: collect(['imported' => true]),
            'created_by' => $asset->created_by ?: 'provider-import',
        ])->save();

        return $asset;
    }

    private function normalizeGithubKey(string $path): string
    {
        if (str_starts_with($path, 'i/')) {
            return $path;
        }

        if (preg_match('#^20\d{2}/#', $path)) {
            return 'i/' . $path;
        }

        return $path;
    }

    private function lastModifiedAt(array $item): ?\Carbon\Carbon
    {
        $value = $item['last_modified'] ?? $item['uploaded'] ?? $item['lastModified'] ?? null;

        return $value ? \Carbon\Carbon::parse($value) : null;
    }

    private function r2ContentType(array $item, string $key): string
    {
        return $item['http_metadata']['contentType']
            ?? $item['http_metadata']['content_type']
            ?? $item['httpMetadata']['contentType']
            ?? $item['content_type']
            ?? $this->guessMimeType($key);
    }

    private function stripLeadingImagePrefix(string $key): string
    {
        return preg_replace('#^i/#', '', $key) ?: $key;
    }

    private function isAssetKey(string $key): bool
    {
        return (bool) preg_match('/\.(avif|bmp|gif|ico|jpe?g|png|svg|webp|pdf|txt|md|json)$/i', $key);
    }

    private function guessMimeType(string $key): string
    {
        return match (strtolower(pathinfo($key, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'avif' => 'image/avif',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'json' => 'application/json',
            default => 'application/octet-stream',
        };
    }
}
