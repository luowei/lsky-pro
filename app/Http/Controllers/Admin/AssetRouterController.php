<?php

namespace App\Http\Controllers\Admin;

use App\AssetRouter\Services\AssetRouterMirrorService;
use App\AssetRouter\Services\AssetRouterProviderStatusService;
use App\AssetRouter\Services\AssetRouterService;
use App\AssetRouter\Services\SecondBrainAssetSyncService;
use App\Http\Controllers\Controller;
use App\Models\AssetRouter\AssetRouterAsset;
use App\Models\AssetRouter\AssetRouterJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetRouterController extends Controller
{
    public function assets(Request $request, AssetRouterService $service): View
    {
        $assets = $service->search($request, $request->user(), true);
        $providerCounts = $service->providerCounts($request, $request->user(), true);

        return view('admin.asset-router.assets', compact('assets', 'providerCounts'));
    }

    public function show(AssetRouterAsset $asset): View
    {
        $asset->load(['owner', 'providerObjects', 'jobs' => fn ($query) => $query->latest()]);

        return view('admin.asset-router.show', compact('asset'));
    }

    public function jobs(Request $request): View
    {
        $jobs = AssetRouterJob::query()
            ->with('asset')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->latest()
            ->paginate((int) $request->query('per_page', 40))
            ->withQueryString();

        return view('admin.asset-router.jobs', compact('jobs'));
    }

    public function providers(AssetRouterProviderStatusService $service): View
    {
        $providers = $service->summary();

        return view('admin.asset-router.providers', compact('providers'));
    }

    public function importProviders(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'source' => ['required', Rule::in(['all', 'r2', 'github'])],
            'prefix' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        Artisan::call('asset-router:import-providers', [
            '--source' => $validated['source'],
            '--prefix' => $validated['prefix'] ?? '',
            '--limit' => (int) ($validated['limit'] ?? 0),
        ]);

        return back()->with('success', trim(Artisan::output()) ?: 'Provider 导入已完成');
    }

    public function retry(AssetRouterJob $job): RedirectResponse
    {
        $job->forceFill([
            'status' => 'queued',
            'last_error' => null,
        ])->save();

        return back()->with('success', '任务已重新排队');
    }

    public function run(
        AssetRouterJob $job,
        AssetRouterMirrorService $mirrorService,
        SecondBrainAssetSyncService $secondBrainSyncService
    ): RedirectResponse {
        if ($job->type === 'sync_second_brain_metadata') {
            $secondBrainSyncService->process($job);
        } else {
            $mirrorService->process($job);
        }

        return back()->with('success', '任务已执行');
    }
}
