<?php

namespace App\Http\Controllers\AssetRouter;

use App\AssetRouter\Services\AssetRouterService;
use App\AssetRouter\Enums\AssetRouterVisibility;
use App\Http\Controllers\Controller;
use App\Models\AssetRouter\AssetRouterAsset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request, AssetRouterService $service): View
    {
        $assets = $service->search($request, $request->user());

        return view('asset-router.images', compact('assets'));
    }

    public function show(AssetRouterAsset $asset, AssetRouterService $service): View
    {
        $service->assertCanManage($asset, request()->user());
        $asset->load(['providerObjects', 'jobs' => fn ($query) => $query->latest()]);

        return view('asset-router.show', compact('asset'));
    }

    public function update(Request $request, AssetRouterAsset $asset, AssetRouterService $service): RedirectResponse
    {
        $service->assertCanManage($asset, $request->user());
        $validated = $request->validate([
            'display_name' => 'required|string|max:255',
        ]);
        $service->rename($asset, $validated['display_name']);

        return back()->with('success', '资源已更新');
    }

    public function visibility(Request $request, AssetRouterAsset $asset, AssetRouterService $service): RedirectResponse
    {
        $service->assertCanManage($asset, $request->user());
        $validated = $request->validate([
            'visibility' => ['required', Rule::in([
                AssetRouterVisibility::Public,
                AssetRouterVisibility::Members,
                AssetRouterVisibility::Private,
            ])],
        ]);
        $service->changeVisibility($asset, $validated['visibility']);

        return back()->with('success', '可见性已更新');
    }

    public function destroy(Request $request, AssetRouterAsset $asset, AssetRouterService $service): RedirectResponse
    {
        $service->assertCanManage($asset, $request->user());
        $service->delete($asset, (bool) $request->boolean('delete_object'));

        return redirect()->route('asset-router.images')->with('success', '资源已删除');
    }
}
