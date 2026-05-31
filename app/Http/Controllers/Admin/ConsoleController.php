<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssetRouter\AssetRouterAsset;
use App\Models\AssetRouter\AssetRouterJob;
use App\Models\Image;
use App\Models\User;
use App\Utils;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ConsoleController extends Controller
{
    public function index(): View
    {
        $format = 'Y-m-d H:i:s';
        $now = Carbon::now();

        $numbers = [
            'today' => AssetRouterAsset::query()->whereBetween('created_at', [$now->copy()->startOfDay()->format($format), $now->copy()->endOfDay()->format($format)])->count(),
            'yesterday' => AssetRouterAsset::query()->whereBetween('created_at', [$now->copy()->yesterday()->startOfDay()->format($format), $now->copy()->yesterday()->endOfDay()->format($format)])->count(),
            'week' => AssetRouterAsset::query()->whereBetween('created_at', [$now->copy()->startOfWeek()->format($format), $now->copy()->endOfWeek()->format($format)])->count(),
            'month' => AssetRouterAsset::query()->whereBetween('created_at', [$now->copy()->startOfMonth()->format($format), $now->copy()->endOfMonth()->format($format)])->count(),
        ];
        $assetRouterOverview = [
            'total' => AssetRouterAsset::query()->count(),
            'public' => AssetRouterAsset::query()->where('visibility', 'public')->count(),
            'private' => AssetRouterAsset::query()->whereIn('visibility', ['members', 'private'])->count(),
            'size' => AssetRouterAsset::query()->sum('size_bytes'),
            'queued_mirrors' => AssetRouterJob::query()->where('type', 'mirror_public_to_github')->where('status', 'queued')->count(),
            'failed_mirrors' => AssetRouterJob::query()->where('type', 'mirror_public_to_github')->where('status', 'failed')->count(),
            'lsky_images' => Image::query()->count(),
            'users' => User::query()->count(),
        ];

        $start = Carbon::now()->parse('-30 day')->startOfDay();
        $end = Carbon::now()->endOfDay();
        $dates = Utils::makeDateRange($start->format('Y-m-d'), $end->format('Y-m-d'));

        $fields = ['公开资源', '私有资源', '新用户'];

        $assets = AssetRouterAsset::query()
            ->whereBetween('created_at', [$start->format($format), $end->format($format)])
            ->get(['visibility', 'created_at'])
            ->transform(function (AssetRouterAsset $asset) {
                $asset['date'] = $asset->created_at->format('Y-m-d');
                return $asset;
            })->groupBy('date');

        $users = User::query()
            ->whereBetween('created_at', [$start->format($format), $end->format($format)])
            ->get()
            ->transform(function (User $user) {
                $user['date'] = $user->created_at->format('Y-m-d');
                return $user;
            })->groupBy('date');

        $data = collect(array_map(fn() => 0, array_flip($dates)));
        $array = [
            $data->merge($assets->map(fn(Collection $items) => $items->where('visibility', 'public')->count())),
            $data->merge($assets->map(fn(Collection $items) => $items->where('visibility', '!=', 'public')->count())),
            $data->merge($users->map(fn(Collection $items) => $items->count())),
        ];
        $datasets = collect($fields)->transform(function ($item, $index) use ($dates, $array) {
            return [
                'name' => $item,
                'type' => 'line',
                'data' => $array[$index]->values(),
            ];
        });

        return view('admin.console.index', compact('fields', 'numbers', 'dates', 'datasets', 'assetRouterOverview'));
    }
}
