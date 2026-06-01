@props(['title' => 'API Token', 'defaultName' => 'PicGo'])

@php
    $tokens = Auth::user()->tokens()->latest()->limit(10)->get();
@endphp

<div class="bg-white rounded-md shadow-custom p-4 space-y-4">
    <div>
        <p class="font-semibold text-gray-700">{{ $title }}</p>
        <p class="text-sm text-gray-500 mt-1">Token 默认脱敏展示，可复制，也可点击显示按钮临时查看明文。</p>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 text-emerald-700 rounded-md p-3 text-sm">{{ session('success') }}</div>
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
                <th class="px-3 py-2 text-left">Token</th>
                <th class="px-3 py-2 text-left">最近使用</th>
                <th class="px-3 py-2 text-left">创建时间</th>
                <th class="px-3 py-2 text-left">操作</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-700">
            @forelse($tokens as $token)
                @php
                    $plainTextToken = null;

                    if ($token->encrypted_plain_text_token) {
                        try {
                            $plainTextToken = Crypt::decryptString($token->encrypted_plain_text_token);
                        } catch (Throwable $e) {
                            $plainTextToken = null;
                        }
                    }
                @endphp
                <tr>
                    <td class="px-3 py-2">{{ $token->name }}</td>
                    <td class="px-3 py-2 min-w-[260px]">
                        @if($plainTextToken)
                            <div class="flex items-center gap-2">
                                <input type="password" readonly class="api-token-value flex-1 rounded border-gray-200 bg-gray-50 text-xs font-mono" value="{{ $plainTextToken }}">
                                <button type="button" class="toggle-api-token py-1 px-2 rounded bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs" title="显示/隐藏 Token">
                                    <i class="fa fa-eye"></i>
                                </button>
                                <button type="button" class="copy-api-token py-1 px-2 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-600 text-xs">复制</button>
                            </div>
                        @else
                            <span class="text-xs text-gray-400">旧 Token 不可查看，请重新生成</span>
                        @endif
                    </td>
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
                <tr><td class="px-3 py-6" colspan="5"><x-no-data message="暂无 Token" /></td></tr>
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
        $('.toggle-api-token').on('click', function () {
            const input = $(this).closest('div').find('.api-token-value');
            const icon = $(this).find('i');
            const isHidden = input.attr('type') === 'password';

            input.attr('type', isHidden ? 'text' : 'password');
            icon.toggleClass('fa-eye', !isHidden).toggleClass('fa-eye-slash', isHidden);
        });

        $('.copy-api-token').on('click', function () {
            const token = $(this).closest('div').find('.api-token-value').val();
            navigator.clipboard.writeText(token).then(() => {
                toastr.success('复制成功');
            }).catch(() => {
                toastr.warning('复制失败');
            });
        });
    </script>
@endpush
