@section('title', 'Asset Router Provider')

<x-app-layout>
    <div class="my-6 md:my-9">
        <div class="bg-white rounded-md shadow-custom overflow-hidden">
            <div class="px-4 py-3 border-b">
                <p class="font-semibold text-gray-700">Provider 状态</p>
                <p class="text-sm text-gray-500">Asset Router 写入、公开镜像与 legacy fallback 的当前配置和对象状态汇总。</p>
            </div>
            <div class="divide-y">
                @foreach($providers as $provider)
                    <div class="p-4 grid grid-cols-1 md:grid-cols-4 gap-3 text-sm">
                        <div>
                            <p class="font-semibold text-gray-700">{{ $provider['provider'] }}</p>
                            <p class="text-gray-500">{{ $provider['role'] }}</p>
                        </div>
                        <div>
                            <p class="{{ $provider['enabled'] ? 'text-green-600' : 'text-gray-400' }}">
                                {{ $provider['enabled'] ? 'enabled' : 'disabled' }}
                            </p>
                            @if(isset($provider['bucket']))<p class="text-gray-500 break-all">{{ $provider['bucket'] }}</p>@endif
                            @if(isset($provider['repo']))<p class="text-gray-500 break-all">{{ $provider['repo'] }}@{{ $provider['branch'] }}</p>@endif
                            @if(isset($provider['base_url']))<p class="text-gray-500 break-all">{{ $provider['base_url'] }}</p>@endif
                        </div>
                        <div class="md:col-span-2 flex flex-wrap gap-2">
                            @forelse($provider['status_counts'] as $status => $count)
                                <span class="px-2 py-1 bg-gray-100 rounded">{{ $status }}: {{ $count }}</span>
                            @empty
                                <span class="text-gray-400">暂无对象记录</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
