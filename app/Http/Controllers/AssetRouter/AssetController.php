<?php

namespace App\Http\Controllers\AssetRouter;

use App\AssetRouter\Services\AssetRouterService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetController extends Controller
{
    public function index(Request $request, AssetRouterService $service): View
    {
        $assets = $service->search($request, $request->user());

        return view('asset-router.images', compact('assets'));
    }
}
