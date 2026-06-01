<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class ApiTokenController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:80',
        ]);

        $name = $validated['name'] ?: 'PicGo';
        $createdToken = $request->user()->createToken($name);
        $token = $createdToken->plainTextToken;
        $createdToken->accessToken->forceFill([
            'encrypted_plain_text_token' => Crypt::encryptString($token),
        ])->save();

        return back()->with('success', 'Token 已生成，可在列表中复制或显示明文。');
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
