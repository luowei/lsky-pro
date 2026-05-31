<?php

namespace App\Http\Controllers\Api\AssetRouter;

use App\AssetRouter\Services\AssetRouterService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetRouter\AssetUploadRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
}
