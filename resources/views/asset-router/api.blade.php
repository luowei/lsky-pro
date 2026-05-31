@section('title', 'Asset Router 接口')

<x-app-layout>
    <div class="my-6 md:my-9 space-y-6">
        <div class="bg-white rounded-md shadow-custom p-4 space-y-3">
            <p class="font-semibold text-gray-700">Base URL</p>
            <x-code>{{ url('/api/asset-router/v1') }}</x-code>
        </div>

        <div class="bg-white rounded-md shadow-custom p-4 space-y-3">
            <p class="font-semibold text-gray-700">上传资源</p>
            <x-code>curl -H "Authorization: Bearer &lt;token&gt;" \
  -F "file=@image.png" \
  -F "visibility=public" \
  {{ url('/api/asset-router/v1/assets') }}</x-code>
            <p class="text-sm text-gray-500">`visibility=public` 返回公开稳定入口；`members` 或 `private` 返回成员入口。</p>
        </div>

        <div class="bg-white rounded-md shadow-custom p-4 space-y-3">
            <p class="font-semibold text-gray-700">状态</p>
            <x-code>curl {{ url('/api/asset-router/v1/status') }}</x-code>
        </div>

        <div class="bg-white rounded-md shadow-custom p-4 space-y-3">
            <p class="font-semibold text-gray-700">CLI</p>
            <x-code>ASSET_ROUTER_BASE_URL={{ url('/api/asset-router/v1') }} \
ASSET_ROUTER_TOKEN=&lt;token&gt; \
tools/asset-router-cli upload image.png --visibility public</x-code>
            <p class="text-sm text-gray-500">CLI 只调用 lsky-pro Asset Router API，不直接持有 R2 或 GitHub 密钥。</p>
        </div>
    </div>
</x-app-layout>
