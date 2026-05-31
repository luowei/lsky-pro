<?php

namespace App\Http\Controllers\Api\AssetRouter;

use App\Http\Controllers\Controller;
use App\Models\AssetRouter\AssetRouterJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class JobController extends Controller
{
    public function index(Request $request): Response
    {
        $jobs = AssetRouterJob::query()
            ->with('asset')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->latest()
            ->paginate((int) $request->query('per_page', 30))
            ->withQueryString();

        return $this->success('success', [
            'jobs' => $jobs,
        ]);
    }

    public function show(AssetRouterJob $job): Response
    {
        return $this->success('success', [
            'job' => $job->load(['asset', 'asset.providerObjects']),
        ]);
    }
}
