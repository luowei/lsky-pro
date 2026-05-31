<?php

namespace App\Http\Controllers\AssetRouter;

use App\AssetRouter\Enums\AssetRouterVisibility;
use App\Http\Controllers\Controller;
use App\Models\AssetRouter\AssetRouterAsset;
use App\Models\AssetRouter\AssetRouterJob;
use App\Utils;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $total = AssetRouterAsset::query()->count();
        $public = AssetRouterAsset::query()->where('visibility', AssetRouterVisibility::Public)->count();
        $private = AssetRouterAsset::query()->whereIn('visibility', [
            AssetRouterVisibility::Members,
            AssetRouterVisibility::Private,
        ])->count();
        $size = AssetRouterAsset::query()->sum('size_bytes');
        $queuedMirrors = AssetRouterJob::query()->where('type', 'mirror_public_to_github')->where('status', 'queued')->count();
        $failedMirrors = AssetRouterJob::query()->where('type', 'mirror_public_to_github')->where('status', 'failed')->count();

        $start = Carbon::now()->parse('-30 day')->startOfDay();
        $end = Carbon::now()->endOfDay();
        $dates = Utils::makeDateRange($start->format('Y-m-d'), $end->format('Y-m-d'));
        $data = collect(array_map(fn() => 0, array_flip($dates)));

        $assets = AssetRouterAsset::query()
            ->whereBetween('created_at', [$start, $end])
            ->get(['visibility', 'created_at'])
            ->transform(function (AssetRouterAsset $asset) {
                $asset['date'] = $asset->created_at->format('Y-m-d');
                return $asset;
            })
            ->groupBy('date');

        $fields = ['公开资源', '私有资源'];
        $publicSeries = $data->merge($assets->map(fn (Collection $items) => $items->where('visibility', AssetRouterVisibility::Public)->count()));
        $privateSeries = $data->merge($assets->map(fn (Collection $items) => $items->where('visibility', '!=', AssetRouterVisibility::Public)->count()));
        $datasets = collect($fields)->transform(function ($item, $index) use ($publicSeries, $privateSeries) {
            return [
                'name' => $item,
                'type' => 'line',
                'data' => ($index === 0 ? $publicSeries : $privateSeries)->values(),
            ];
        });

        return view('asset-router.dashboard', compact(
            'total',
            'public',
            'private',
            'size',
            'queuedMirrors',
            'failedMirrors',
            'fields',
            'dates',
            'datasets',
        ));
    }
}
