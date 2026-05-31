<?php

namespace Tests\Feature\AssetRouter;

use App\Models\AssetRouter\AssetRouterAsset;
use App\Models\AssetRouter\AssetRouterJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssetRouterUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_public_asset()
    {
        config([
            'asset-router.r2.enabled' => false,
            'asset-router.local_root' => storage_path('framework/testing/asset-router'),
            'asset-router.public_base_url' => 'https://assets.example.test',
            'asset-router.members_base_url' => 'https://assets.example.test/m',
        ]);

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('asset.png', 8, 'image/png');

        $response = $this->actingAs($user)->post(route('asset-router.upload.store'), [
            'file' => $file,
            'visibility' => 'public',
        ]);

        $response->assertRedirect(route('asset-router.upload'));
        $this->assertDatabaseHas('asset_router_assets', [
            'owner_user_id' => $user->id,
            'visibility' => 'public',
            'mime_type' => 'image/png',
            'status' => 'pending_mirror',
        ]);

        $asset = AssetRouterAsset::query()->firstOrFail();
        $this->assertStringStartsWith('i/', $asset->key);
        $this->assertSame("https://assets.example.test/{$asset->key}", $asset->canonical_url);
        $this->assertDatabaseHas('asset_router_jobs', [
            'asset_id' => $asset->id,
            'type' => 'mirror_public_to_github',
            'status' => 'queued',
        ]);
    }

    public function test_authenticated_user_can_upload_members_asset()
    {
        config([
            'asset-router.r2.enabled' => false,
            'asset-router.local_root' => storage_path('framework/testing/asset-router'),
            'asset-router.public_base_url' => 'https://assets.example.test',
            'asset-router.members_base_url' => 'https://assets.example.test/m',
        ]);

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('asset-private.png', 8, 'image/png');

        $response = $this->actingAs($user)->post(route('asset-router.upload.store'), [
            'file' => $file,
            'visibility' => 'members',
        ]);

        $response->assertRedirect(route('asset-router.upload'));

        $asset = AssetRouterAsset::query()->firstOrFail();
        $this->assertSame('members', $asset->visibility);
        $this->assertSame('active', $asset->status);
        $this->assertSame("https://assets.example.test/m/{$asset->key}", $asset->url);
        $this->assertDatabaseCount('asset_router_jobs', 0);
    }

    public function test_sanctum_api_can_upload_asset_router_asset()
    {
        config([
            'asset-router.r2.enabled' => false,
            'asset-router.local_root' => storage_path('framework/testing/asset-router'),
            'asset-router.public_base_url' => 'https://assets.example.test',
            'asset-router.members_base_url' => 'https://assets.example.test/m',
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/asset-router/v1/assets', [
            'file' => UploadedFile::fake()->create('agent-upload.png', 8, 'image/png'),
            'visibility' => 'members',
            'display_name' => 'Agent upload',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.asset.display_name', 'Agent upload')
            ->assertJsonPath('data.asset.visibility', 'members');

        $asset = AssetRouterAsset::query()->firstOrFail();
        $this->assertSame("https://assets.example.test/m/{$asset->key}", $response->json('data.links.url'));
    }

    public function test_queued_public_asset_can_be_mirrored_to_github_jsdelivr()
    {
        config([
            'asset-router.r2.enabled' => false,
            'asset-router.local_root' => storage_path('framework/testing/asset-router'),
            'asset-router.public_base_url' => 'https://assets.example.test',
            'asset-router.github.repo' => 'luowei/second-brain-image-assets',
            'asset-router.github.branch' => 'main',
            'asset-router.github.token' => 'test-token',
            'asset-router.github.jsdelivr_base_url' => 'https://cdn.jsdelivr.net/gh/luowei/second-brain-image-assets@main',
        ]);

        Http::fake([
            'api.github.com/*' => Http::sequence()
                ->push('', 404)
                ->push([
                    'content' => ['sha' => 'content-sha'],
                    'commit' => ['sha' => 'commit-sha'],
                ], 201),
        ]);

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('asset.png', 8, 'image/png');

        $this->actingAs($user)->post(route('asset-router.upload.store'), [
            'file' => $file,
            'visibility' => 'public',
        ]);

        $this->artisan('asset-router:run-jobs')
            ->expectsOutputToContain('succeeded')
            ->assertExitCode(0);

        $asset = AssetRouterAsset::query()->firstOrFail();
        $job = AssetRouterJob::query()->firstOrFail();

        $this->assertSame('active', $asset->refresh()->status);
        $this->assertSame('succeeded', $job->refresh()->status);
        $this->assertDatabaseHas('asset_router_provider_objects', [
            'asset_id' => $asset->id,
            'provider' => 'github-jsdelivr',
            'provider_key' => $asset->key,
            'url' => "https://cdn.jsdelivr.net/gh/luowei/second-brain-image-assets@main/{$asset->key}",
            'status' => 'present',
        ]);
    }
}
