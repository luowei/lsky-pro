@section('title', 'Asset Router Provider')

<x-app-layout>
    <div class="my-6 md:my-9 space-y-4">
        <form class="bg-white rounded-md shadow-custom p-4 grid grid-cols-1 md:grid-cols-5 gap-3 text-sm" method="post" action="{{ route('admin.asset-router.providers.import') }}">
            @csrf
            <div>
                <label class="block text-gray-500 mb-1">导入来源</label>
                <select name="source" class="w-full rounded bg-gray-100 border-0 text-sm">
                    <option value="all">R2 + GitHub</option>
                    <option value="r2">仅 R2</option>
                    <option value="github">仅 GitHub / jsDelivr</option>
                </select>
            </div>
            <div>
                <label class="block text-gray-500 mb-1">Key 前缀</label>
                <input type="text" name="prefix" class="w-full rounded bg-gray-100 border-0 text-sm" placeholder="例如 i/2026/">
            </div>
            <div>
                <label class="block text-gray-500 mb-1">导入上限</label>
                <input type="number" name="limit" min="0" max="100000" value="0" class="w-full rounded bg-gray-100 border-0 text-sm">
            </div>
            <div class="md:col-span-2 flex flex-col justify-end">
                <button class="py-2 px-4 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md font-semibold">导入 Provider 存量资源</button>
                <p class="mt-2 text-xs text-gray-500">可重复执行；已有 key 会合并 provider 记录，不会复制或删除实际对象。limit 为 0 表示不限制。</p>
            </div>
        </form>

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
