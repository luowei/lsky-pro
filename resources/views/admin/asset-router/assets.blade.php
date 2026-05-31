@section('title', 'Asset Router 资源管理')

<x-app-layout>
    <div class="my-6 md:my-9 space-y-4">
        <form class="bg-white rounded-md shadow-custom p-4 flex flex-col md:flex-row md:items-center gap-3" method="get" action="{{ route('admin.asset-router.assets') }}">
            <input type="text" name="keyword" value="{{ request('keyword') }}" class="rounded bg-gray-100 border-0 text-sm md:w-72" placeholder="搜索名称、key、sha256">
            <select name="visibility" class="rounded bg-gray-100 border-0 text-sm md:w-44">
                <option value="">全部可见性</option>
                <option value="public" @selected(request('visibility') === 'public')>公开</option>
                <option value="members" @selected(request('visibility') === 'members')>成员</option>
                <option value="private" @selected(request('visibility') === 'private')>私有</option>
            </select>
            <button class="py-2 px-4 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md text-sm font-semibold">搜索</button>
            <a href="{{ route('admin.asset-router.jobs') }}" class="py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm">任务</a>
        </form>

        <div class="bg-white rounded-md shadow-custom overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">资源</th>
                    <th class="px-4 py-3 text-left">Owner</th>
                    <th class="px-4 py-3 text-left">状态</th>
                    <th class="px-4 py-3 text-left">Provider</th>
                    <th class="px-4 py-3 text-left">时间</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                @forelse($assets as $asset)
                    <tr>
                        <td class="px-4 py-3">
                            <a class="text-blue-500 hover:text-blue-600" href="{{ route('admin.asset-router.assets.show', $asset) }}">{{ $asset->display_name }}</a>
                            <p class="text-gray-500 break-all"><code>{{ $asset->key }}</code></p>
                        </td>
                        <td class="px-4 py-3">{{ $asset->owner?->email ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $asset->visibility }} · {{ $asset->status }}</td>
                        <td class="px-4 py-3">{{ $asset->providerObjects->pluck('provider')->implode(', ') ?: '-' }}</td>
                        <td class="px-4 py-3">{{ $asset->created_at }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-8" colspan="5"><x-no-data message="暂无 Asset Router 资源" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $assets->links() }}
    </div>
</x-app-layout>
