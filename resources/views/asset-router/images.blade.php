@section('title', 'Asset Router 我的图片')

<x-app-layout>
    <div class="my-6 md:my-9 space-y-4">
        <form class="bg-white rounded-md shadow-custom p-4 flex flex-col md:flex-row md:items-center gap-3" method="get" action="{{ route('asset-router.images') }}">
            <input type="text" name="keyword" value="{{ request('keyword') }}" class="rounded bg-gray-100 border-0 text-sm md:w-72" placeholder="搜索名称、key、sha256">
            <select name="visibility" class="rounded bg-gray-100 border-0 text-sm md:w-44">
                <option value="">全部可见性</option>
                <option value="public" @selected(request('visibility') === 'public')>公开</option>
                <option value="members" @selected(request('visibility') === 'members')>成员</option>
                <option value="private" @selected(request('visibility') === 'private')>私有</option>
            </select>
            <button class="py-2 px-4 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md text-sm font-semibold">搜索</button>
            <a href="{{ route('asset-router.upload') }}" class="py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm">上传</a>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            @forelse($assets as $asset)
                <div class="bg-white rounded-md shadow-custom overflow-hidden">
                    <div class="aspect-video bg-gray-100 flex items-center justify-center overflow-hidden">
                        @if($asset->asset_type === 'image')
                            <img src="{{ $asset->url }}" alt="{{ $asset->display_name }}" class="w-full h-full object-cover">
                        @else
                            <i class="fas fa-file text-5xl text-gray-400"></i>
                        @endif
                    </div>
                    <div class="p-4 space-y-2 text-sm">
                        <div class="flex justify-between items-center">
                            <p class="font-semibold text-gray-700 truncate" title="{{ $asset->display_name }}">{{ $asset->display_name }}</p>
                            <span class="text-xs rounded px-2 py-1 bg-gray-100 text-gray-600">{{ $asset->visibility }}</span>
                        </div>
                        <p class="text-gray-500 truncate"><code>{{ $asset->key }}</code></p>
                        <p class="text-gray-500">{{ \App\Utils::formatSize($asset->size_bytes) }} · {{ $asset->mime_type }}</p>
                        <p class="text-gray-500">Provider: {{ $asset->providerObjects->pluck('provider')->implode(', ') ?: 'unknown' }}</p>
                        <p><a class="text-blue-500 hover:text-blue-600 break-all" href="{{ $asset->url }}" target="_blank">{{ $asset->url }}</a></p>
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-md shadow-custom p-8 md:col-span-2 xl:col-span-3">
                    <x-no-data message="暂无 Asset Router 资源" />
                </div>
            @endforelse
        </div>

        <div>
            {{ $assets->links() }}
        </div>
    </div>
</x-app-layout>
