@section('title', 'Asset Router 资源详情')

<x-app-layout>
    <div class="my-6 md:my-9 space-y-6">
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-700 rounded-md p-3 text-sm">{{ session('success') }}</div>
        @endif

        <div class="bg-white rounded-md shadow-custom overflow-hidden">
            <div class="aspect-video bg-gray-100 flex items-center justify-center overflow-hidden">
                @if($asset->asset_type === 'image')
                    <img src="{{ $asset->url }}" alt="{{ $asset->display_name }}" class="w-full h-full object-contain">
                @else
                    <i class="fas fa-file text-6xl text-gray-400"></i>
                @endif
            </div>
            <div class="p-4 space-y-3 text-sm">
                <p class="text-lg font-semibold text-gray-700">{{ $asset->display_name }}</p>
                <p class="text-gray-500 break-all"><code>{{ $asset->key }}</code></p>
                <p><a class="text-blue-500 hover:text-blue-600 break-all" href="{{ $asset->url }}" target="_blank">{{ $asset->url }}</a></p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-gray-600">
                    <p>Visibility: {{ $asset->visibility }}</p>
                    <p>Status: {{ $asset->status }}</p>
                    <p>Size: {{ \App\Utils::formatSize($asset->size_bytes) }}</p>
                    <p>MIME: {{ $asset->mime_type }}</p>
                    <p>SHA256: <code class="break-all">{{ $asset->sha256 }}</code></p>
                    <p>Created: {{ $asset->created_at }}</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <form class="bg-white rounded-md shadow-custom p-4 space-y-3" method="post" action="{{ route('asset-router.images.update', $asset) }}">
                @csrf
                @method('put')
                <p class="font-semibold text-gray-700">重命名</p>
                <input name="display_name" value="{{ old('display_name', $asset->display_name) }}" class="w-full rounded bg-gray-100 border-0 text-sm">
                @error('display_name')<p class="text-red-500 text-sm">{{ $message }}</p>@enderror
                <button class="py-2 px-4 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md text-sm">保存</button>
            </form>

            <form class="bg-white rounded-md shadow-custom p-4 space-y-3" method="post" action="{{ route('asset-router.images.visibility', $asset) }}">
                @csrf
                @method('put')
                <p class="font-semibold text-gray-700">可见性</p>
                <select name="visibility" class="w-full rounded bg-gray-100 border-0 text-sm">
                    <option value="public" @selected($asset->visibility === 'public')>public</option>
                    <option value="members" @selected($asset->visibility === 'members')>members</option>
                    <option value="private" @selected($asset->visibility === 'private')>private</option>
                </select>
                <button class="py-2 px-4 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md text-sm">更新</button>
            </form>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-md shadow-custom p-4 space-y-3">
                <p class="font-semibold text-gray-700">Provider Objects</p>
                @forelse($asset->providerObjects as $object)
                    <div class="border-b pb-2 text-sm text-gray-600">
                        <p>{{ $object->provider }} · {{ $object->status }}</p>
                        <p class="break-all"><code>{{ $object->provider_key }}</code></p>
                        @if($object->url)<p class="break-all"><a class="text-blue-500" href="{{ $object->url }}" target="_blank">{{ $object->url }}</a></p>@endif
                    </div>
                @empty
                    <x-no-data message="暂无 provider 记录" />
                @endforelse
            </div>

            <div class="bg-white rounded-md shadow-custom p-4 space-y-3">
                <p class="font-semibold text-gray-700">Jobs</p>
                @forelse($asset->jobs as $job)
                    <div class="border-b pb-2 text-sm text-gray-600">
                        <p>{{ $job->type }} · {{ $job->status }} · attempts {{ $job->attempts }}</p>
                        @if($job->last_error)<p class="text-red-500 break-all">{{ $job->last_error }}</p>@endif
                    </div>
                @empty
                    <x-no-data message="暂无任务" />
                @endforelse
            </div>
        </div>

        <form method="post" action="{{ route('asset-router.images.destroy', $asset) }}" class="bg-white rounded-md shadow-custom p-4 space-y-3" onsubmit="return confirm('确认删除此资源？')">
            @csrf
            @method('delete')
            <label class="flex items-center space-x-2 text-sm text-gray-600">
                <input type="checkbox" name="delete_object" value="1" class="rounded">
                <span>同时删除主存储对象</span>
            </label>
            <button class="py-2 px-4 bg-red-500 hover:bg-red-600 text-white rounded-md text-sm">删除资源</button>
        </form>
    </div>
</x-app-layout>
