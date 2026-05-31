<?php

namespace App\AssetRouter\Clients;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GitHubAssetClient
{
    public function putObject(string $key, string $contents, string $mimeType): array
    {
        $repo = (string) config('asset-router.github.repo');
        $branch = (string) config('asset-router.github.branch');
        $token = (string) config('asset-router.github.token');

        if (! $repo || ! str_contains($repo, '/')) {
            throw new RuntimeException('GITHUB_ASSET_REPO must use owner/repo format.');
        }

        if (! $token) {
            throw new RuntimeException('GITHUB_ASSET_TOKEN is required to mirror public assets.');
        }

        $path = ltrim($key, '/');
        $sha = $this->existingSha($repo, $path, $branch);
        $payload = [
            'message' => "Mirror asset {$path}",
            'content' => base64_encode($contents),
            'branch' => $branch,
            'committer' => [
                'name' => config('asset-router.github.commit_author_name'),
                'email' => config('asset-router.github.commit_author_email'),
            ],
            'author' => [
                'name' => config('asset-router.github.commit_author_name'),
                'email' => config('asset-router.github.commit_author_email'),
            ],
        ];

        if ($sha) {
            $payload['sha'] = $sha;
        }

        $response = $this->request()
            ->put("repos/{$repo}/contents/{$path}", $payload);

        if (! $response->successful()) {
            throw new RuntimeException("GitHub mirror failed for {$path}: {$response->status()} {$response->body()}");
        }

        return [
            'provider_key' => $path,
            'content_sha' => $response->json('content.sha'),
            'commit_sha' => $response->json('commit.sha'),
            'mime_type' => $mimeType,
        ];
    }

    private function existingSha(string $repo, string $path, string $branch): ?string
    {
        $response = $this->request()
            ->get("repos/{$repo}/contents/{$path}", ['ref' => $branch]);

        if ($response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new RuntimeException("GitHub lookup failed for {$path}: {$response->status()} {$response->body()}");
        }

        return $response->json('sha');
    }

    private function request(): PendingRequest
    {
        return Http::withToken((string) config('asset-router.github.token'))
            ->baseUrl('https://api.github.com')
            ->accept('application/vnd.github+json')
            ->withHeaders([
                'X-GitHub-Api-Version' => '2022-11-28',
            ])
            ->timeout(60);
    }
}
