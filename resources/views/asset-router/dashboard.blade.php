@section('title', 'Asset Router 仪表盘')

<x-app-layout>
    <div class="my-6 md:my-9 space-y-8">
        <div>
            <p class="mb-3 font-semibold text-lg text-gray-700">概览</p>
            <div class="relative grid grid-cols-1 md:grid-cols-2 xl:grid-cols-6 gap-4">
                <div class="flex justify-between rounded-md bg-white p-3 overflow-hidden shadow-custom">
                    <div class="flex flex-col justify-between space-y-2 w-[80%]">
                        <p class="font-bold text-2xl text-blue-700 truncate">{{ \App\Utils::shortenNumber($total) }}</p>
                        <p class="text-md text-gray-600">资源总数</p>
                    </div>
                    <i class="fas fa-cubes text-blue-600 text-2xl"></i>
                </div>
                <div class="flex justify-between rounded-md bg-white p-3 overflow-hidden shadow-custom">
                    <div class="flex flex-col justify-between space-y-2 w-[80%]">
                        <p class="font-bold text-2xl text-emerald-700 truncate">{{ \App\Utils::shortenNumber($public) }}</p>
                        <p class="text-md text-gray-600">公开资源</p>
                    </div>
                    <i class="fas fa-globe text-emerald-600 text-2xl"></i>
                </div>
                <div class="flex justify-between rounded-md bg-white p-3 overflow-hidden shadow-custom">
                    <div class="flex flex-col justify-between space-y-2 w-[80%]">
                        <p class="font-bold text-2xl text-zinc-700 truncate">{{ \App\Utils::shortenNumber($private) }}</p>
                        <p class="text-md text-gray-600">私有资源</p>
                    </div>
                    <i class="fas fa-lock text-zinc-600 text-2xl"></i>
                </div>
                <div class="flex justify-between rounded-md bg-white p-3 overflow-hidden shadow-custom">
                    <div class="flex flex-col justify-between space-y-2 w-[80%]">
                        <p class="font-bold text-2xl text-cyan-700 truncate">{{ \App\Utils::formatSize($size) }}</p>
                        <p class="text-md text-gray-600">资源体积</p>
                    </div>
                    <i class="fas fa-database text-cyan-600 text-2xl"></i>
                </div>
                <div class="flex justify-between rounded-md bg-white p-3 overflow-hidden shadow-custom">
                    <div class="flex flex-col justify-between space-y-2 w-[80%]">
                        <p class="font-bold text-2xl text-amber-700 truncate">{{ \App\Utils::shortenNumber($queuedMirrors) }}</p>
                        <p class="text-md text-gray-600">待镜像</p>
                    </div>
                    <i class="fas fa-code-branch text-amber-600 text-2xl"></i>
                </div>
                <div class="flex justify-between rounded-md bg-white p-3 overflow-hidden shadow-custom">
                    <div class="flex flex-col justify-between space-y-2 w-[80%]">
                        <p class="font-bold text-2xl text-red-700 truncate">{{ \App\Utils::shortenNumber($failedMirrors) }}</p>
                        <p class="text-md text-gray-600">镜像失败</p>
                    </div>
                    <i class="fas fa-exclamation-circle text-red-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div>
            <p class="mb-3 font-semibold text-lg text-gray-700">趋势</p>
            <div class="relative p-4 rounded-md bg-white h-80 shadow-custom" id="chart">
                <canvas></canvas>
            </div>
        </div>

        <div>
            <p class="mb-3 font-semibold text-lg text-gray-700">Provider</p>
            <div class="relative rounded-md bg-white overflow-hidden shadow-custom">
                <dl>
                    <div class="bg-gray-50 px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">公开入口</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ config('asset-router.public_base_url') }}</dd>
                    </div>
                    <div class="bg-white px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">成员入口</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ config('asset-router.members_base_url') }}</dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">R2 写入</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ config('asset-router.r2.enabled') ? '已启用' : '未启用，本地开发 fallback' }}</dd>
                    </div>
                    <div class="bg-white px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">GitHub 镜像仓</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ config('asset-router.github.repo') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('js/echarts/echarts.min.js') }}"></script>
        <script>
            $(function () {
                let chartDom = document.getElementById('chart');
                let myChart = echarts.init(chartDom);
                let options = {
                    title: {text: '近 30 天资源上传'},
                    tooltip: {trigger: 'axis'},
                    legend: {top: '10%', data: @json($fields)},
                    grid: {left: '3%', right: '3%', bottom: '3%', containLabel: true},
                    xAxis: {type: 'category', boundaryGap: false, data: @json($dates)},
                    yAxis: {type: 'value', minInterval: 1},
                    series: @json($datasets)
                };
                myChart.setOption(options);
                window.onresize = function () { myChart.resize(); };
            })
        </script>
    @endpush
</x-app-layout>
