<?php

namespace App\AssetRouter\Services;

use App\AssetRouter\Enums\AssetRouterProvider;
use App\AssetRouter\Enums\AssetRouterVisibility;
use App\Jobs\AssetRouter\MirrorPublicAssetToGithub;
use App\Models\AssetRouter\AssetRouterAsset;
use App\Models\AssetRouter\AssetRouterJob;
use App\Models\AssetRouter\AssetRouterProviderObject;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetRouterService
{
    public function __construct(
        private AssetKeyFactory $keyFactory,
        private AssetUrlBuilder $urlBuilder,
        private AssetRouterStorage $storage,
    ) {
    }

    public function upload(Request $request, ?User $user = null): AssetRouterAsset
    {
        /** @var UploadedFile $file */
        $file = $request->file('file');
        $visibility = AssetRouterVisibility::normalize($request->input('visibility', config('asset-router.default_visibility')));
        $sha256 = hash_file('sha256', $file->getRealPath());
        $md5 = md5_file($file->getRealPath());
        $key = $this->makeUniqueKey($file, $sha256);
        $extension = $this->keyFactory->extension($file);
        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        return DB::transaction(function () use ($request, $user, $file, $visibility, $sha256, $md5, $key, $extension, $width, $height) {
            $stored = $this->storage->put($key, $file);

            /** @var AssetRouterAsset $asset */
            $asset = AssetRouterAsset::query()->create([
                'owner_user_id' => $user?->id,
                'key' => $key,
                'display_name' => $request->input('display_name') ?: $file->getClientOriginalName(),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'extension' => $extension,
                'size_bytes' => $file->getSize(),
                'sha256' => $sha256,
                'md5' => $md5,
                'width' => $width,
                'height' => $height,
                'visibility' => $visibility,
                'asset_type' => str_starts_with((string) $file->getMimeType(), 'image/') ? 'image' : 'file',
                'status' => AssetRouterVisibility::isPublic($visibility) ? 'pending_mirror' : 'active',
                'canonical_url' => $this->urlBuilder->canonicalUrl($key),
                'members_url' => $this->urlBuilder->membersUrl($key),
                'primary_provider' => AssetRouterProvider::R2,
                'metadata' => [
                    'alt_text' => $request->input('alt_text'),
                    'tags' => array_values(array_filter((array) $request->input('tags', []))),
                ],
                'created_by' => $user?->email,
                'uploaded_ip' => $request->ip(),
            ]);

            AssetRouterProviderObject::query()->create([
                'asset_id' => $asset->id,
                'provider' => $stored['provider'] === 'r2' ? AssetRouterProvider::R2 : $stored['provider'],
                'provider_key' => $stored['provider_key'],
                'url' => $stored['provider'] === 'r2' ? $asset->canonical_url : null,
                'status' => 'present',
                'etag' => $stored['etag'],
            ]);

            if (AssetRouterVisibility::isPublic($visibility)) {
                $job = AssetRouterJob::query()->create([
                    'asset_id' => $asset->id,
                    'type' => 'mirror_public_to_github',
                    'status' => 'queued',
                    'payload' => [
                        'key' => $key,
                        'repo' => config('asset-router.github.repo'),
                        'branch' => config('asset-router.github.branch'),
                    ],
                ]);

                if (config('asset-router.mirror.auto_dispatch')) {
                    MirrorPublicAssetToGithub::dispatch($job->id)->afterCommit();
                }
            }

            return $asset->fresh(['providerObjects', 'jobs']);
        });
    }

    public function search(Request $request, ?User $user = null, bool $global = false): LengthAwarePaginator
    {
        return AssetRouterAsset::query()
            ->with('providerObjects')
            ->when(! $global && $user, fn ($query) => $query->where('owner_user_id', $user->id))
            ->when($request->query('keyword'), function ($query, $keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->where('display_name', 'like', "%{$keyword}%")
                        ->orWhere('original_name', 'like', "%{$keyword}%")
                        ->orWhere('key', 'like', "%{$keyword}%")
                        ->orWhere('sha256', 'like', "%{$keyword}%");
                });
            })
            ->when($request->query('visibility'), fn ($query, $visibility) => $query->where('visibility', $visibility))
            ->latest()
            ->paginate((int) $request->query('per_page', 40))
            ->withQueryString();
    }

    private function makeUniqueKey(UploadedFile $file, string $sha256): string
    {
        $key = $this->keyFactory->make($file, $sha256);
        if (! AssetRouterAsset::query()->where('key', $key)->exists()) {
            return $key;
        }

        $extension = pathinfo($key, PATHINFO_EXTENSION);
        $base = $extension ? substr($key, 0, -1 * (strlen($extension) + 1)) : $key;

        do {
            $candidate = $base . '-' . Str::lower(Str::random(6)) . ($extension ? ".{$extension}" : '');
        } while (AssetRouterAsset::query()->where('key', $candidate)->exists());

        return $candidate;
    }
}
