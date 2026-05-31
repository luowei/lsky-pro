<?php

namespace App\Http\Controllers\Api\AssetRouter;

use App\AssetRouter\Services\AssetRouterService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetRouter\AssetUploadRequest;
use App\Models\AssetRouter\AssetRouterAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use App\AssetRouter\Enums\AssetRouterVisibility;

class AssetController extends Controller
{
    public function index(Request $request, AssetRouterService $service): Response
    {
        return $this->success('success', [
            'assets' => $service->search($request, $request->user()),
        ]);
    }

    public function store(AssetUploadRequest $request, AssetRouterService $service): Response
    {
        $asset = $service->upload($request, $request->user());

        return $this->success('上传成功', [
            'asset' => $asset,
            'links' => $asset->links,
        ]);
    }

    public function show(Request $request, AssetRouterAsset $asset, AssetRouterService $service): Response
    {
        $service->assertCanManage($asset, $request->user());

        return $this->success('success', [
            'asset' => $asset->load(['providerObjects', 'jobs']),
            'links' => $asset->links,
        ]);
    }

    public function update(Request $request, AssetRouterAsset $asset, AssetRouterService $service): Response
    {
        $service->assertCanManage($asset, $request->user());
        $validated = $request->validate([
            'display_name' => 'nullable|string|max:255',
            'visibility' => ['nullable', Rule::in([
                AssetRouterVisibility::Public,
                AssetRouterVisibility::Members,
                AssetRouterVisibility::Private,
            ])],
        ]);

        if (isset($validated['display_name'])) {
            $asset = $service->rename($asset, $validated['display_name']);
        }

        if (isset($validated['visibility'])) {
            $asset = $service->changeVisibility($asset, $validated['visibility']);
        }

        return $this->success('更新成功', [
            'asset' => $asset,
            'links' => $asset->links,
        ]);
    }

    public function destroy(Request $request, AssetRouterAsset $asset, AssetRouterService $service): Response
    {
        $service->assertCanManage($asset, $request->user());
        $service->delete($asset, (bool) $request->boolean('delete_object'));

        return $this->success('删除成功');
    }

    public function links(Request $request, AssetRouterAsset $asset, AssetRouterService $service): Response
    {
        $service->assertCanManage($asset, $request->user());

        return $this->success('success', [
            'asset' => $asset,
            'links' => $asset->links,
        ]);
    }

    public function mirror(Request $request, AssetRouterAsset $asset, AssetRouterService $service): Response
    {
        $service->assertCanManage($asset, $request->user());
        $job = $service->queueMirror($asset);

        return $this->success('镜像任务已排队', [
            'job' => $job,
        ]);
    }

    public function picgoStore(AssetUploadRequest $request, AssetRouterService $service): Response
    {
        $asset = $service->upload($request, $request->user());

        return response([
            'status' => true,
            'success' => true,
            'message' => '上传成功',
            'result' => [$asset->url],
            'data' => [
                'url' => $asset->url,
                'asset' => $asset,
                'links' => $asset->links,
            ],
        ]);
    }
}
