@section('title', 'Asset Router 上传')

<x-app-layout>
    <div class="my-6 md:my-9 space-y-6">
        <div class="bg-white rounded-md shadow-custom p-4">
            <h1 class="tracking-wider text-2xl text-gray-700 mb-2" style="text-shadow: -4px 4px 0 rgb(0 0 0 / 10%);">Asset Upload</h1>
            <p class="text-gray-500 text-sm">默认上传为公开资源：保留 R2 副本，并排队镜像到 GitHub + jsDelivr。关闭公开镜像后，资源只写入 R2 成员入口。</p>

            <form id="asset-router-upload-form" class="mt-5 space-y-4" method="post" action="{{ route('asset-router.upload.store') }}" enctype="multipart/form-data">
                @csrf
                <input id="asset-router-picker" type="file" name="file" class="hidden" multiple>

                <div id="asset-router-dropzone" class="rounded-md border-2 border-dotted border-stone-300 bg-gray-50 hover:bg-gray-100 transition-colors cursor-pointer">
                    <div class="min-h-[220px] sm:min-h-[320px] flex flex-col items-center justify-center p-6 text-center text-gray-500 space-y-4">
                        <i class="fas fa-cloud-upload-alt text-6xl text-gray-400"></i>
                        <div>
                            <p class="text-base text-gray-700">拖拽文件到这里，或点击选择文件</p>
                            <p class="text-sm text-gray-500">支持多文件队列上传；公开开关会应用到本次队列中的所有文件。</p>
                        </div>
                        <button type="button" id="asset-router-pick-button" class="py-2 px-4 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-md text-sm">选择文件</button>
                    </div>
                    @error('file')<p class="text-red-500 text-sm mt-1 px-4 pb-4">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm text-gray-600 mb-2">显示名称</label>
                    <input type="text" name="display_name" class="w-full rounded bg-gray-100 border-0 text-sm" placeholder="默认使用原始文件名；多文件上传时忽略此项">
                </div>
                <input type="hidden" name="visibility" value="members">
                <div class="flex items-center space-x-3">
                    <input type="checkbox" name="visibility" value="public" checked class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="text-sm text-gray-700">公开镜像 GitHub + jsDelivr CDN</span>
                </div>
                <p class="text-xs text-gray-500">关闭后将以 <code>members</code> visibility 上传，返回 <code>{{ config('asset-router.members_base_url') }}</code> 下的稳定入口。</p>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="asset-router-upload-all" class="py-2 px-4 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md text-sm font-semibold">
                        <i class="fas fa-cloud-upload-alt"></i>
                        上传全部
                    </button>
                    <button class="py-2 px-4 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-md text-sm font-semibold">
                        <i class="fas fa-upload"></i>
                        普通表单上传
                    </button>
                </div>
            </form>

            <div id="asset-router-queue" class="mt-5 space-y-2 hidden"></div>

            <div id="asset-router-links" class="mt-5 hidden border-t pt-4">
                <div class="flex items-center justify-between mb-3">
                    <p class="font-semibold text-gray-700">上传结果</p>
                    <button type="button" id="asset-router-copy-all" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded text-xs">复制全部 Markdown</button>
                </div>
                <div class="space-y-2 text-sm"></div>
            </div>
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

        <script type="text/html" id="asset-router-queue-item-template">
            <div data-id="__id__" class="relative overflow-hidden rounded-md bg-gray-50 p-3">
                <div class="absolute inset-y-0 left-0 bg-indigo-100 upload-progress" style="width: 0%"></div>
                <div class="relative flex items-center gap-3">
                    <div class="w-12 h-12 bg-gray-200 rounded overflow-hidden flex items-center justify-center shrink-0">
                        <img src="__src__" alt="" class="w-full h-full object-cover hidden preview-image">
                        <i class="fas fa-file text-gray-400 preview-file"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-gray-700 truncate">__name__</p>
                        <p class="text-xs text-gray-500"><span>__size__</span> · <span class="upload-status">等待上传</span></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" data-action="upload" class="w-9 h-9 rounded-full bg-white hover:bg-gray-100 text-gray-600"><i class="fas fa-upload"></i></button>
                        <button type="button" data-action="remove" class="w-9 h-9 rounded-full bg-white hover:bg-gray-100 text-gray-600"><i class="fas fa-times"></i></button>
                    </div>
                </div>
            </div>
        </script>

        @push('scripts')
            <script>
                (() => {
                    const form = document.getElementById('asset-router-upload-form');
                    const picker = document.getElementById('asset-router-picker');
                    const dropzone = document.getElementById('asset-router-dropzone');
                    const queueEl = document.getElementById('asset-router-queue');
                    const linksEl = document.getElementById('asset-router-links');
                    const linksList = linksEl.querySelector('.space-y-2');
                    const template = document.getElementById('asset-router-queue-item-template').innerHTML;
                    const queue = new Map();

                    const formatSize = (bytes) => {
                        const units = ['Bytes', 'KB', 'MB', 'GB'];
                        let size = bytes || 0;
                        let unit = 0;
                        while (size >= 1024 && unit < units.length - 1) {
                            size /= 1024;
                            unit++;
                        }
                        return `${size.toFixed(unit === 0 ? 0 : 2)} ${units[unit]}`;
                    };
                    const escapeHtml = (value) => String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    const addFiles = (files) => {
                        Array.from(files).forEach((file) => {
                            const id = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
                            const objectUrl = file.type.startsWith('image/') ? URL.createObjectURL(file) : '';
                            const html = template
                                .replace(/__id__/g, id)
                                .replace(/__src__/g, objectUrl)
                                .replace(/__name__/g, escapeHtml(file.name))
                                .replace(/__size__/g, formatSize(file.size));
                            queueEl.insertAdjacentHTML('beforeend', html);
                            const item = queueEl.querySelector(`[data-id="${id}"]`);
                            if (objectUrl) {
                                item.querySelector('.preview-image').classList.remove('hidden');
                                item.querySelector('.preview-file').classList.add('hidden');
                            }
                            queue.set(id, {file, item, objectUrl, status: 'waiting'});
                        });
                        queueEl.classList.toggle('hidden', queue.size === 0);
                    };
                    const setStatus = (entry, status, message) => {
                        entry.status = status;
                        const statusEl = entry.item.querySelector('.upload-status');
                        statusEl.className = 'upload-status';
                        if (status === 'success') statusEl.classList.add('text-green-700');
                        if (status === 'error') statusEl.classList.add('text-red-500');
                        statusEl.textContent = message;
                    };
                    const uploadEntry = (id) => {
                        const entry = queue.get(id);
                        if (!entry || entry.status === 'uploading' || entry.status === 'success') return;

                        const data = new FormData();
                        data.append('_token', form.querySelector('input[name="_token"]').value);
                        data.append('file', entry.file);
                        data.append('visibility', form.querySelector('input[name="visibility"][type="checkbox"]').checked ? 'public' : 'members');
                        const displayName = form.querySelector('input[name="display_name"]').value.trim();
                        if (displayName && queue.size === 1) data.append('display_name', displayName);

                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', form.action);
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                        xhr.setRequestHeader('Accept', 'application/json');
                        xhr.upload.onprogress = (event) => {
                            if (!event.lengthComputable) return;
                            const progress = Math.round(event.loaded / event.total * 100);
                            entry.item.querySelector('.upload-progress').style.width = `${progress}%`;
                            setStatus(entry, 'uploading', `上传中...${progress}%`);
                        };
                        xhr.onload = () => {
                            let response = {};
                            try {
                                response = JSON.parse(xhr.responseText || '{}');
                            } catch (error) {
                                response = {status: false, message: '响应解析失败'};
                            }
                            if (xhr.status >= 200 && xhr.status < 300 && response.status) {
                                setStatus(entry, 'success', '上传成功');
                                entry.item.querySelector('[data-action="upload"]').classList.add('hidden');
                                const markdown = response.data?.links?.markdown || response.data?.asset?.url || '';
                                linksList.insertAdjacentHTML('beforeend', `<p class="select-all bg-gray-50 hover:bg-gray-100 rounded px-2 py-1 overflow-scroll scrollbar-none">${escapeHtml(markdown)}</p>`);
                                linksEl.classList.remove('hidden');
                                return;
                            }
                            setStatus(entry, 'error', response.message || '上传失败');
                        };
                        xhr.onerror = () => setStatus(entry, 'error', '网络错误');
                        setStatus(entry, 'uploading', '准备上传');
                        xhr.send(data);
                    };

                    document.getElementById('asset-router-pick-button').addEventListener('click', () => picker.click());
                    dropzone.addEventListener('click', (event) => {
                        if (event.target.id !== 'asset-router-pick-button') picker.click();
                    });
                    picker.addEventListener('change', () => addFiles(picker.files));
                    ['dragenter', 'dragover'].forEach((name) => {
                        dropzone.addEventListener(name, (event) => {
                            event.preventDefault();
                            dropzone.classList.add('border-indigo-400', 'bg-indigo-50');
                        });
                    });
                    ['dragleave', 'drop'].forEach((name) => {
                        dropzone.addEventListener(name, (event) => {
                            event.preventDefault();
                            dropzone.classList.remove('border-indigo-400', 'bg-indigo-50');
                        });
                    });
                    dropzone.addEventListener('drop', (event) => addFiles(event.dataTransfer.files));
                    queueEl.addEventListener('click', (event) => {
                        const button = event.target.closest('button[data-action]');
                        if (!button) return;
                        const item = button.closest('[data-id]');
                        const id = item.dataset.id;
                        if (button.dataset.action === 'remove') {
                            const entry = queue.get(id);
                            if (entry?.objectUrl) URL.revokeObjectURL(entry.objectUrl);
                            queue.delete(id);
                            item.remove();
                            queueEl.classList.toggle('hidden', queue.size === 0);
                            return;
                        }
                        uploadEntry(id);
                    });
                    document.getElementById('asset-router-upload-all').addEventListener('click', () => queue.forEach((_, id) => uploadEntry(id)));
                    document.getElementById('asset-router-copy-all').addEventListener('click', () => {
                        const text = Array.from(linksList.querySelectorAll('p')).map((item) => item.textContent).join('\n');
                        navigator.clipboard.writeText(text);
                    });
                })();
            </script>
        @endpush
    </div>
</x-app-layout>
