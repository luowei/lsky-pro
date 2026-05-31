<?php

namespace App\Http\Controllers\Api\AssetRouter;

use App\AssetRouter\Services\AssetRouterProviderStatusService;
use App\AssetRouter\Services\AssetRouterService;
use App\Http\Controllers\Controller;
use App\Models\AssetRouter\AssetRouterAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProviderController extends Controller
{
    public function index(AssetRouterProviderStatusService $service): Response
    {
        return $this->success('success', [
            'providers' => $service->summary(),
        ]);
    }

    public function probe(
        Request $request,
        AssetRouterAsset $asset,
        AssetRouterService $assetService,
        AssetRouterProviderStatusService $providerStatusService
    ): Response {
        $assetService->assertCanManage($asset, $request->user());

        return $this->success('探活完成', [
            'asset' => $asset->fresh(['providerObjects']),
            'providers' => $providerStatusService->probe($asset),
        ]);
    }
}
