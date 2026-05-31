@section('title', 'Asset Router 资源详情')

<x-app-layout>
    <div class="my-6 md:my-9 space-y-6">
        <div class="bg-white rounded-md shadow-custom p-4 space-y-3">
            <p class="text-lg font-semibold text-gray-700">{{ $asset->display_name }}</p>
            <p class="text-sm text-gray-500 break-all"><code>{{ $asset->key }}</code></p>
            <p class="text-sm"><a class="text-blue-500 break-all" href="{{ $asset->url }}" target="_blank">{{ $asset->url }}</a></p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-sm text-gray-600">
                <p>Owner: {{ $asset->owner?->email ?: '-' }}</p>
                <p>Visibility: {{ $asset->visibility }}</p>
                <p>Status: {{ $asset->status }}</p>
                <p>Size: {{ \App\Utils::formatSize($asset->size_bytes) }}</p>
                <p>MIME: {{ $asset->mime_type }}</p>
                <p>Created: {{ $asset->created_at }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-md shadow-custom p-4 space-y-3">
                <p class="font-semibold text-gray-700">Provider Objects</p>
                @forelse($asset->providerObjects as $object)
                    <div class="border-b pb-2 text-sm text-gray-600">
                        <p>{{ $object->provider }} · {{ $object->status }}</p>
                        <p class="break-all"><code>{{ $object->provider_key }}</code></p>
                        @if($object->url)<p class="break-all"><a class="text-blue-500" href="{{ $object->url }}" target="_blank">{{ $object->url }}</a></p>@endif
                        @if($object->last_error)<p class="text-red-500 break-all">{{ $object->last_error }}</p>@endif
                    </div>
                @empty
                    <x-no-data message="暂无 provider 记录" />
                @endforelse
            </div>

            <div class="bg-white rounded-md shadow-custom p-4 space-y-3">
                <p class="font-semibold text-gray-700">Jobs</p>
                @forelse($asset->jobs as $job)
                    <div class="border-b pb-2 text-sm text-gray-600 flex justify-between gap-3">
                        <div>
                            <p>{{ $job->type }} · {{ $job->status }} · attempts {{ $job->attempts }}</p>
                            @if($job->last_error)<p class="text-red-500 break-all">{{ $job->last_error }}</p>@endif
                        </div>
                        <form method="post" action="{{ route('admin.asset-router.jobs.run', $job) }}">
                            @csrf
                            <button class="py-1.5 px-3 bg-indigo-500 hover:bg-indigo-600 text-white rounded text-xs">执行</button>
                        </form>
                    </div>
                @empty
                    <x-no-data message="暂无任务" />
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
