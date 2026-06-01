<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:80',
        ]);

        $name = $validated['name'] ?: 'PicGo';
        $token = $request->user()->createToken($name)->plainTextToken;

        return back()->with('plain_api_token', $token)
            ->with('success', 'Token 已生成，请立即复制保存。');
    }

    public function destroy(Request $request, string $tokenId): RedirectResponse
    {
        $request->user()->tokens()->whereKey($tokenId)->delete();

        return back()->with('success', 'Token 已删除');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->user()->tokens()->delete();

        return back()->with('success', '所有 Token 已清空');
    }
}
