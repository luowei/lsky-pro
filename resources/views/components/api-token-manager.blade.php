@props(['title' => 'API Token', 'defaultName' => 'PicGo'])

@php
    $tokens = Auth::user()->tokens()->latest()->limit(10)->get();
@endphp

<div class="bg-white rounded-md shadow-custom p-4 space-y-4">
    <div>
        <p class="font-semibold text-gray-700">{{ $title }}</p>
        <p class="text-sm text-gray-500 mt-1">生成后的 token 明文只显示一次，请复制后保存到 PicGo 或 CLI。</p>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 text-emerald-700 rounded-md p-3 text-sm">{{ session('success') }}</div>
    @endif

    @if(session('plain_api_token'))
        <div class="bg-amber-50 border border-amber-100 rounded-md p-3 space-y-2">
            <p class="text-sm font-semibold text-amber-700">新 Token</p>
            <div class="flex flex-col md:flex-row gap-2">
                <input id="plain-api-token" readonly class="flex-1 rounded bg-white border-amber-200 text-sm" value="{{ session('plain_api_token') }}">
                <button type="button" id="copy-plain-api-token" class="py-2 px-4 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md text-sm">复制 Token</button>
            </div>
        </div>
    @endif

    <form method="post" action="{{ route('user.api-tokens.store') }}" class="flex flex-col md:flex-row md:items-end gap-3">
        @csrf
        <div class="flex-1">
            <label class="block text-sm font-medium text-gray-700" for="api-token-name">Token 名称</label>
            <x-input id="api-token-name" name="name" value="{{ old('name', $defaultName) }}" placeholder="PicGo" />
            @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <button class="py-2 px-4 bg-indigo-500 hover:bg-indigo-600 text-white rounded-md text-sm font-semibold">生成 Token</button>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50 text-gray-500">
            <tr>
                <th class="px-3 py-2 text-left">名称</th>
                <th class="px-3 py-2 text-left">最近使用</th>
                <th class="px-3 py-2 text-left">创建时间</th>
                <th class="px-3 py-2 text-left">操作</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-700">
            @forelse($tokens as $token)
                <tr>
                    <td class="px-3 py-2">{{ $token->name }}</td>
                    <td class="px-3 py-2">{{ $token->last_used_at ?: '-' }}</td>
                    <td class="px-3 py-2">{{ $token->created_at }}</td>
                    <td class="px-3 py-2">
                        <form method="post" action="{{ route('user.api-tokens.destroy', $token) }}" onsubmit="return confirm('确认删除此 Token？')">
                            @csrf
                            @method('delete')
                            <button class="text-red-500 hover:text-red-600">删除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td class="px-3 py-6" colspan="4"><x-no-data message="暂无 Token" /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($tokens->isNotEmpty())
        <form method="post" action="{{ route('user.api-tokens.clear') }}" onsubmit="return confirm('确认清空所有 Token？')">
            @csrf
            @method('delete')
            <button class="py-2 px-4 bg-red-50 hover:bg-red-100 text-red-600 rounded-md text-sm">清空所有 Token</button>
        </form>
    @endif
</div>

@push('scripts')
    <script>
        $('#copy-plain-api-token').on('click', function () {
            const token = $('#plain-api-token').val();
            navigator.clipboard.writeText(token).then(() => {
                toastr.success('复制成功');
            }).catch(() => {
                toastr.warning('复制失败');
            });
        });
    </script>
@endpush
