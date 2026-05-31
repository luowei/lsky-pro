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

    public function test_sanctum_api_can_update_and_delete_asset_router_asset()
    {
        config([
            'asset-router.r2.enabled' => false,
            'asset-router.local_root' => storage_path('framework/testing/asset-router'),
            'asset-router.public_base_url' => 'https://assets.example.test',
            'asset-router.members_base_url' => 'https://assets.example.test/m',
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/asset-router/v1/assets', [
            'file' => UploadedFile::fake()->create('agent-upload.png', 8, 'image/png'),
            'visibility' => 'members',
        ])->assertOk();

        $asset = AssetRouterAsset::query()->firstOrFail();

        $this->putJson("/api/asset-router/v1/assets/{$asset->id}", [
            'display_name' => 'Renamed by agent',
            'visibility' => 'public',
        ])->assertOk()
            ->assertJsonPath('data.asset.display_name', 'Renamed by agent')
            ->assertJsonPath('data.asset.visibility', 'public');

        $this->assertDatabaseHas('asset_router_jobs', [
            'asset_id' => $asset->id,
            'type' => 'mirror_public_to_github',
            'status' => 'queued',
        ]);

        $this->deleteJson("/api/asset-router/v1/assets/{$asset->id}")
            ->assertOk();

        $this->assertSoftDeleted('asset_router_assets', [
            'id' => $asset->id,
        ]);
    }

    public function test_picgo_compatible_upload_accepts_image_field()
    {
        config([
            'asset-router.r2.enabled' => false,
            'asset-router.local_root' => storage_path('framework/testing/asset-router'),
            'asset-router.public_base_url' => 'https://assets.example.test',
            'asset-router.members_base_url' => 'https://assets.example.test/m',
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/asset-router/v1/picgo/upload', [
            'image' => UploadedFile::fake()->create('picgo.png', 8, 'image/png'),
            'visibility' => 'public',
        ]);

        $asset = AssetRouterAsset::query()->firstOrFail();

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('result.0', "https://assets.example.test/{$asset->key}")
            ->assertJsonPath('data.url', "https://assets.example.test/{$asset->key}");
    }

    public function test_second_brain_metadata_sync_job_can_be_processed()
    {
        config([
            'asset-router.r2.enabled' => false,
            'asset-router.local_root' => storage_path('framework/testing/asset-router'),
            'asset-router.public_base_url' => 'https://assets.example.test',
            'asset-router.members_base_url' => 'https://assets.example.test/m',
            'asset-router.second_brain.sync_url' => 'https://second-brain.example.test/assets/sync',
            'asset-router.second_brain.sync_token' => 'sync-token',
        ]);

        Http::fake([
            'second-brain.example.test/*' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('asset-router.upload.store'), [
            'file' => UploadedFile::fake()->create('asset.png', 8, 'image/png'),
            'visibility' => 'members',
        ]);

        $this->assertDatabaseHas('asset_router_jobs', [
            'type' => 'sync_second_brain_metadata',
            'status' => 'queued',
        ]);

        $this->artisan('asset-router:run-jobs')
            ->expectsOutputToContain('succeeded')
            ->assertExitCode(0);

        Http::assertSent(fn ($request) => $request->url() === 'https://second-brain.example.test/assets/sync'
            && $request['event'] === 'asset.created'
            && $request['asset']['visibility'] === 'members');

        $this->assertDatabaseHas('asset_router_jobs', [
            'type' => 'sync_second_brain_metadata',
            'status' => 'succeeded',
        ]);
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

    public function test_api_can_report_providers_links_jobs_and_probe_asset()
    {
        config([
            'asset-router.r2.enabled' => false,
            'asset-router.local_root' => storage_path('framework/testing/asset-router'),
            'asset-router.public_base_url' => 'https://assets.example.test',
            'asset-router.members_base_url' => 'https://assets.example.test/m',
            'asset-router.github.repo' => 'luowei/second-brain-image-assets',
            'asset-router.github.branch' => 'main',
            'asset-router.github.jsdelivr_base_url' => 'https://cdn.jsdelivr.net/gh/luowei/second-brain-image-assets@main',
        ]);

        Http::fake([
            'cdn.jsdelivr.net/*' => Http::response('', 200),
        ]);

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/asset-router/v1/assets', [
            'file' => UploadedFile::fake()->create('agent-upload.png', 8, 'image/png'),
            'visibility' => 'public',
        ])->assertOk();

        $asset = AssetRouterAsset::query()->firstOrFail();

        $this->getJson('/api/asset-router/v1/providers')
            ->assertOk()
            ->assertJsonPath('data.providers.0.provider', 'r2');

        $this->getJson("/api/asset-router/v1/assets/{$asset->id}/links")
            ->assertOk()
            ->assertJsonPath('data.links.url', "https://assets.example.test/{$asset->key}");

        $this->postJson("/api/asset-router/v1/assets/{$asset->id}/probe")
            ->assertOk()
            ->assertJsonPath('data.providers.0.status', 'present')
            ->assertJsonPath('data.providers.1.provider', 'github-jsdelivr');

        $this->postJson("/api/asset-router/v1/assets/{$asset->id}/mirror")
            ->assertOk()
            ->assertJsonPath('data.job.type', 'mirror_public_to_github');

        $this->getJson('/api/asset-router/v1/jobs?type=mirror_public_to_github')
            ->assertOk()
            ->assertJsonPath('data.jobs.total', 2);
    }

    public function test_upload_can_write_r2_via_cloudflare_api_token()
    {
        config([
            'asset-router.r2.enabled' => true,
            'asset-router.r2.account_id' => 'cf-account',
            'asset-router.r2.api_token' => 'cf-token',
            'asset-router.r2.access_key_id' => null,
            'asset-router.r2.secret_access_key' => null,
            'asset-router.r2.bucket' => 'second-brain-assets-prod',
            'asset-router.public_base_url' => 'https://assets.example.test',
        ]);

        Http::fake([
            'api.cloudflare.com/client/v4/accounts/cf-account/r2/buckets/second-brain-assets-prod/objects/*' => Http::response([
                'success' => true,
                'result' => [
                    'etag' => 'r2-etag',
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('asset-router.upload.store'), [
            'file' => UploadedFile::fake()->create('asset.png', 8, 'image/png'),
            'visibility' => 'public',
        ])->assertRedirect(route('asset-router.upload'));

        $asset = AssetRouterAsset::query()->firstOrFail();

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && str_contains($request->url(), "/objects/{$asset->key}")
            && $request->hasHeader('Authorization', 'Bearer cf-token'));

        $this->assertDatabaseHas('asset_router_provider_objects', [
            'asset_id' => $asset->id,
            'provider' => 'r2',
            'etag' => 'r2-etag',
            'status' => 'present',
        ]);
    }
}
