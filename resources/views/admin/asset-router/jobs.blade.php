@section('title', 'Asset Router 任务')

<x-app-layout>
    <div class="my-6 md:my-9 space-y-4">
        @if(session('success'))
            <div class="bg-emerald-50 text-emerald-700 rounded-md p-3 text-sm">{{ session('success') }}</div>
        @endif

        <form class="bg-white rounded-md shadow-custom p-4 flex flex-col md:flex-row md:items-center gap-3" method="get" action="{{ route('admin.asset-router.jobs') }}">
            <select name="status" class="rounded bg-gray-100 border-0 text-sm md:w-44">
                <option value="">全部状态</option>
                @foreach(['queued', 'running', 'succeeded', 'failed', 'cancelled'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
            <button class="py-2 px-4 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md text-sm font-semibold">筛选</button>
            <a href="{{ route('admin.asset-router.assets') }}" class="py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm">资源</a>
        </form>

        <div class="bg-white rounded-md shadow-custom overflow-hidden">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-gray-500">
                <tr>
                    <th class="px-4 py-3 text-left">任务</th>
                    <th class="px-4 py-3 text-left">资源</th>
                    <th class="px-4 py-3 text-left">状态</th>
                    <th class="px-4 py-3 text-left">错误</th>
                    <th class="px-4 py-3 text-left">操作</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                @forelse($jobs as $job)
                    <tr>
                        <td class="px-4 py-3">
                            <p>{{ $job->type }}</p>
                            <p class="text-gray-500">{{ $job->id }}</p>
                        </td>
                        <td class="px-4 py-3">
                            @if($job->asset)
                                <a class="text-blue-500" href="{{ route('admin.asset-router.assets.show', $job->asset) }}">{{ $job->asset->display_name }}</a>
                                <p class="text-gray-500 break-all"><code>{{ $job->asset->key }}</code></p>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $job->status }} · attempts {{ $job->attempts }}</td>
                        <td class="px-4 py-3 text-red-500 break-all">{{ $job->last_error }}</td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2">
                                <form method="post" action="{{ route('admin.asset-router.jobs.retry', $job) }}">
                                    @csrf
                                    <button class="py-1.5 px-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs">重试</button>
                                </form>
                                <form method="post" action="{{ route('admin.asset-router.jobs.run', $job) }}">
                                    @csrf
                                    <button class="py-1.5 px-3 bg-indigo-500 hover:bg-indigo-600 text-white rounded text-xs">执行</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-8" colspan="5"><x-no-data message="暂无任务" /></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{ $jobs->links() }}
    </div>
</x-app-layout>
