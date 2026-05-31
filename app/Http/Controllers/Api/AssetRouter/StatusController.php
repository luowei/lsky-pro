<?php

namespace App\Http\Controllers\Api\AssetRouter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

class StatusController extends Controller
{
    public function __invoke(): Response
    {
        return $this->success('success', [
            'service' => 'lsky-pro asset-router control-plane',
            'enabled' => (bool) config('asset-router.enabled'),
            'public_base_url' => config('asset-router.public_base_url'),
            'members_base_url' => config('asset-router.members_base_url'),
            'r2_enabled' => (bool) config('asset-router.r2.enabled'),
            'github_repo' => config('asset-router.github.repo'),
        ]);
    }
}
