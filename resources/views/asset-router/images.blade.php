@section('title', 'Asset Router 我的图片')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/justified-gallery/justifiedGallery.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/viewer-js/viewer.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/context-js/context-js.css') }}">
@endpush

<x-app-layout>
    <div class="relative flex flex-col gap-2 lg:flex-row lg:justify-between lg:items-center px-2 py-2 z-[3] top-0 left-0 right-0 bg-white border-solid border-b">
        <div class="flex flex-wrap items-center gap-2">
            @foreach([
                '' => ['label' => '全部', 'count' => $providerCounts['all'] ?? 0],
                'r2' => ['label' => 'R2 主图床', 'count' => $providerCounts['r2'] ?? 0],
                'github-jsdelivr' => ['label' => 'GitHub / jsDelivr', 'count' => $providerCounts['github-jsdelivr'] ?? 0],
            ] as $provider => $tab)
                <a
                    href="{{ route('asset-router.images', array_filter(array_merge(request()->except('page'), ['provider' => $provider]), fn ($value) => $value !== null && $value !== '')) }}"
                    class="text-sm py-2 px-3 rounded flex items-center gap-2 {{ request('provider', '') === $provider ? 'bg-indigo-500 text-white' : 'text-gray-800 hover:bg-gray-100' }}"
                >
                    <span>{{ $tab['label'] }}</span>
                    <span class="text-xs {{ request('provider', '') === $provider ? 'text-indigo-100' : 'text-gray-500' }}">{{ $tab['count'] }}</span>
                </a>
            @endforeach
        </div>

        <form class="flex flex-wrap items-center gap-2" method="get" action="{{ route('asset-router.images') }}">
            <input type="hidden" name="provider" value="{{ request('provider') }}">
            <input type="text" name="keyword" value="{{ request('keyword') }}" class="px-2.5 py-1.5 border-0 outline-none rounded bg-gray-100 text-sm w-48 md:w-56" placeholder="搜索名称、key、sha256">
            <select name="visibility" class="rounded bg-gray-100 border-0 text-sm w-32">
                <option value="">全部</option>
                <option value="public" @selected(request('visibility') === 'public')>公开</option>
                <option value="members" @selected(request('visibility') === 'members')>成员</option>
                <option value="private" @selected(request('visibility') === 'private')>私有</option>
            </select>
            <button class="text-sm py-2 px-3 bg-indigo-500 hover:bg-indigo-600 text-white rounded">搜索</button>
            <a href="{{ route('asset-router.upload') }}" class="text-sm py-2 px-3 hover:bg-gray-100 rounded text-gray-800">上传</a>
        </form>
    </div>

    <div class="relative inset-0 h-full overflow-hidden">
        <div id="images-scroll" class="absolute inset-0 overflow-y-scroll select-none">
            <div id="images-grid" class="px-2 py-3">
                @forelse($assets as $asset)
                    @php
                        $width = $asset->width ?: 320;
                        $height = $asset->height ?: 180;
                        $providers = $asset->providerObjects->pluck('provider')->unique()->values()->all();
                        $links = $asset->links->all();
                        $payload = [
                            'id' => $asset->id,
                            'display_name' => $asset->display_name,
                            'key' => $asset->key,
                            'url' => $asset->url,
                            'links' => $links,
                            'visibility' => $asset->visibility,
                            'status' => $asset->status,
                            'providers' => $providers,
                            'size' => \App\Utils::formatSize($asset->size_bytes),
                            'mime_type' => $asset->mime_type,
                            'dimensions' => $asset->width && $asset->height ? "{$asset->width} * {$asset->height}" : '-',
                            'sha256' => $asset->sha256 ?: '-',
                            'md5' => $asset->md5 ?: '-',
                            'created_at' => $asset->created_at?->format('Y-m-d H:i:s') ?: '-',
                            'detail_url' => route('asset-router.images.show', $asset),
                        ];
                    @endphp
                    <a href="javascript:void(0)"
                       data-id="{{ $asset->id }}"
                       data-json='@json($payload)'
                       class="asset-router-item relative cursor-default rounded outline outline-2 outline-offset-2 outline-transparent"
                    >
                        <div class="image-mask absolute left-0 right-0 bottom-0 h-20 z-[1] bg-gradient-to-t from-black" onclick="$(this).siblings('img').trigger('click')">
                            <div class="absolute left-2 bottom-2 text-white z-[2] w-[90%]">
                                <p class="text-sm truncate filename" title="{{ $asset->display_name }}">{{ $asset->display_name }}</p>
                                <p class="text-xs truncate" title="{{ $asset->key }}">{{ implode(', ', $providers) ?: 'unknown' }} · {{ \App\Utils::formatSize($asset->size_bytes) }}</p>
                            </div>
                        </div>
                        <img alt="{{ $asset->display_name }}"
                             data-original="{{ $asset->url }}"
                             src="{{ $asset->url }}"
                             width="{{ $width }}"
                             height="{{ $height }}"
                        >
                    </a>
                @empty
                    <div class="bg-white rounded-md shadow-custom p-8">
                        <x-no-data message="当前分类暂无 Asset Router 资源" />
                    </div>
                @endforelse
            </div>

            <div class="px-4 pb-6">
                {{ $assets->links() }}
            </div>
        </div>

        <div id="drawer-mask" class="absolute hidden inset-0 bg-gray-500 bg-opacity-50 z-[2]" onclick="drawer.close()"></div>
        <div id="drawer" class="absolute bg-white w-72 md:w-80 top-0 -right-[1000px] bottom-0 z-[2] flex flex-col transition-all duration-300">
            <div class="flex justify-between items-center text-md px-3 py-1 border-b">
                <span class="text-gray-600 truncate" id="drawer-title"></span>
                <a href="javascript:drawer.close()" class="p-2"><i class="fas fa-times text-blue-500"></i></a>
            </div>
            <div id="drawer-content" class="overflow-y-auto"></div>
        </div>
    </div>

    <script type="text/html" id="asset-detail-tpl">
        <div class="my-4 px-4 space-y-3">
            <div>
                <span class="text-sm font-semibold">资源名称</span>
                <p class="my-2 break-words text-gray-700">__display_name__</p>
            </div>
            <div>
                <span class="text-sm font-semibold">Key</span>
                <p class="my-2 break-words text-gray-700">__key__</p>
            </div>
            <div>
                <span class="text-sm font-semibold">Provider</span>
                <p class="my-2 break-words text-gray-700">__providers__</p>
            </div>
            <div>
                <span class="text-sm font-semibold">可见性 / 状态</span>
                <p class="my-2 break-words text-gray-700">__visibility__ / __status__</p>
            </div>
            <div>
                <span class="text-sm font-semibold">大小 / 类型</span>
                <p class="my-2 break-words text-gray-700">__size__ / __mime_type__</p>
            </div>
            <div>
                <span class="text-sm font-semibold">尺寸</span>
                <p class="my-2 break-words text-gray-700">__dimensions__</p>
            </div>
            <div>
                <span class="text-sm font-semibold">SHA256</span>
                <p class="my-2 break-words text-gray-700">__sha256__</p>
            </div>
            <div>
                <span class="text-sm font-semibold">MD5</span>
                <p class="my-2 break-words text-gray-700">__md5__</p>
            </div>
            <div>
                <span class="text-sm font-semibold">创建时间</span>
                <p class="my-2 break-words text-gray-700">__created_at__</p>
            </div>
        </div>
    </script>

    @push('scripts')
        <script src="{{ asset('js/justified-gallery/jquery.justifiedGallery.min.js') }}"></script>
        <script src="{{ asset('js/viewer-js/viewer.min.js') }}"></script>
        <script src="{{ asset('js/context-js/context-js.js') }}"></script>
        <script src="{{ asset('js/clipboard/clipboard.min.js') }}"></script>
        <script>
            const IMAGES_SCROLL = '#images-scroll';
            const IMAGES_GRID = '#images-grid';
            const ASSET_ITEM = '.asset-router-item';
            const $photos = $(IMAGES_GRID);
            const $drawer = $('#drawer');
            const $drawerMask = $('#drawer-mask');

            const drawer = {
                open(title, content) {
                    $drawerMask.fadeIn();
                    $drawer.css('right', 0);
                    $drawer.find('#drawer-title').html(title);
                    $drawer.find('#drawer-content').html(content);
                },
                close() {
                    $drawerMask.fadeOut();
                    $drawer.css('right', '-1000px');
                }
            };

            if ($photos.find(ASSET_ITEM).length > 0) {
                $photos.justifiedGallery({
                    rowHeight: 180,
                    margins: 16,
                    captions: false,
                    border: 10,
                    waitThumbnailsLoad: false,
                });
                new Viewer(document.getElementById('images-grid'), {url: 'data-original'});
            }

            const copyText = value => {
                navigator.clipboard.writeText(value).then(() => {
                    toastr.success('复制成功');
                }).catch(() => {
                    toastr.warning('复制失败');
                });
            };

            const detailHtml = item => $('#asset-detail-tpl').html()
                .replace(/__display_name__/g, item.display_name)
                .replace(/__key__/g, item.key)
                .replace(/__providers__/g, item.providers.length ? item.providers.join(', ') : 'unknown')
                .replace(/__visibility__/g, item.visibility)
                .replace(/__status__/g, item.status)
                .replace(/__size__/g, item.size)
                .replace(/__mime_type__/g, item.mime_type)
                .replace(/__dimensions__/g, item.dimensions)
                .replace(/__sha256__/g, item.sha256)
                .replace(/__md5__/g, item.md5)
                .replace(/__created_at__/g, item.created_at);

            const actions = {
                refresh: {text: '刷新', action: () => window.location.reload()},
                open: {
                    text: '新窗口打开',
                    action: e => window.open($(e).data('json').url),
                },
                copies: {
                    text: '复制链接',
                    subMenu: [
                        {text: '复制 URL', action: e => copyText($(e).data('json').links.url)},
                        {text: '复制 Html', action: e => copyText($(e).data('json').links.html)},
                        {text: '复制 BBCode', action: e => copyText($(e).data('json').links.bbcode)},
                        {text: '复制 Markdown', action: e => copyText($(e).data('json').links.markdown)},
                        {text: '复制 Markdown with link', action: e => copyText($(e).data('json').links.markdown_with_link)},
                    ],
                },
                detail: {
                    text: '详细信息',
                    action: e => {
                        const item = $(e).data('json');
                        drawer.open(item.display_name, detailHtml(item));
                    },
                },
                manage: {
                    text: '管理资源',
                    action: e => window.location.href = $(e).data('json').detail_url,
                },
            };

            context.init({
                fadeSpeed: 100,
                filter: function () {},
                above: 'auto',
                preventDoubleContext: true,
                compress: false
            });

            context.attach(IMAGES_SCROLL, {
                data: [actions.refresh],
            });

            context.attach(ASSET_ITEM, {
                data: [
                    {header: 'Asset Router'},
                    actions.refresh,
                    actions.open,
                    actions.copies,
                    actions.detail,
                    {divider: true},
                    actions.manage,
                ],
            });
        </script>
    @endpush
</x-app-layout>
