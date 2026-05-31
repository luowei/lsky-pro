<?php

namespace App\Http\Controllers\AssetRouter;

use App\AssetRouter\Services\AssetRouterService;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssetRouter\AssetUploadRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class UploadController extends Controller
{
    public function create(): View
    {
        return view('asset-router.upload');
    }

    public function store(AssetUploadRequest $request, AssetRouterService $service): RedirectResponse|Response
    {
        $asset = $service->upload($request, $request->user());

        if ($request->expectsJson() || $request->ajax()) {
            return $this->success('上传成功', [
                'asset' => $asset,
                'links' => $asset->links,
            ]);
        }

        return redirect()
            ->route('asset-router.upload')
            ->with('asset_router_uploaded', $asset->toArray());
    }
}
