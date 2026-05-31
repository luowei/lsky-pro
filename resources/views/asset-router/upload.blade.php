@section('title', 'Asset Router 上传')

<x-app-layout>
    <div class="my-6 md:my-9 space-y-6">
        <div class="bg-white rounded-md shadow-custom p-4">
            <h1 class="tracking-wider text-2xl text-gray-700 mb-2" style="text-shadow: -4px 4px 0 rgb(0 0 0 / 10%);">Asset Upload</h1>
            <p class="text-gray-500 text-sm">默认上传为公开资源：保留 R2 副本，并排队镜像到 GitHub + jsDelivr。关闭公开镜像后，资源只写入 R2 成员入口。</p>

            <form class="mt-5 space-y-4" method="post" action="{{ route('asset-router.upload.store') }}" enctype="multipart/form-data">
                @csrf
                <div>
                    <label class="block text-sm text-gray-600 mb-2">文件</label>
                    <input type="file" name="file" class="w-full rounded bg-gray-100 border-0 text-sm" required>
                    @error('file')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-2">显示名称</label>
                    <input type="text" name="display_name" class="w-full rounded bg-gray-100 border-0 text-sm" placeholder="默认使用原始文件名">
                </div>
                <input type="hidden" name="visibility" value="members">
                <div class="flex items-center space-x-3">
                    <input type="checkbox" name="visibility" value="public" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">公开镜像 GitHub + jsDelivr CDN</span>
                </div>
                <p class="text-xs text-gray-500">关闭后将以 <code>members</code> visibility 上传，返回 <code>{{ config('asset-router.members_base_url') }}</code> 下的稳定入口。</p>
                <button class="py-2 px-4 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md text-sm font-semibold">
                    <i class="fas fa-cloud-upload-alt"></i>
                    上传
                </button>
            </form>
        </div>

        @if(session('asset_router_uploaded'))
            @php($asset = session('asset_router_uploaded'))
            <div class="bg-white rounded-md shadow-custom p-4 space-y-3">
                <p class="font-semibold text-gray-700">上传成功</p>
                <div class="text-sm text-gray-600 space-y-2">
                    <p><span class="font-semibold">Key：</span><code>{{ $asset['key'] }}</code></p>
                    <p><span class="font-semibold">URL：</span><code class="select-all">{{ $asset['url'] }}</code></p>
                    <p><span class="font-semibold">Markdown：</span><code class="select-all">{{ $asset['links']['markdown'] ?? '' }}</code></p>
                    <p><span class="font-semibold">状态：</span>{{ $asset['status'] }}</p>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
