<?php

namespace App\AssetRouter\Services;

use App\AssetRouter\Enums\AssetRouterVisibility;

class AssetUrlBuilder
{
    public function canonicalUrl(string $key): string
    {
        return $this->join(config('asset-router.public_base_url'), $key);
    }

    public function membersUrl(string $key): string
    {
        return $this->join(config('asset-router.members_base_url'), $key);
    }

    public function jsdelivrUrl(string $key): string
    {
        $baseUrl = config('asset-router.github.jsdelivr_base_url');

        return $this->join($baseUrl, $key);
    }

    public function urlForVisibility(string $key, string $visibility): string
    {
        return AssetRouterVisibility::isPublic($visibility)
            ? $this->canonicalUrl($key)
            : $this->membersUrl($key);
    }

    private function join(?string $baseUrl, string $key): string
    {
        $baseUrl = rtrim((string) $baseUrl, '/');
        $key = ltrim($key, '/');

        return $baseUrl ? "{$baseUrl}/{$key}" : "/{$key}";
    }
}
