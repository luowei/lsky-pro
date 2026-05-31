<?php

namespace App\Http\Requests\AssetRouter;

use App\AssetRouter\Enums\AssetRouterVisibility;
use App\Http\Requests\FormRequest;
use Illuminate\Validation\Rule;

class AssetUploadRequest extends FormRequest
{
    public function rules()
    {
        return [
            'file' => 'required|file|max:51200',
            'visibility' => ['nullable', Rule::in([
                AssetRouterVisibility::Public,
                AssetRouterVisibility::Members,
                AssetRouterVisibility::Private,
            ])],
            'display_name' => 'nullable|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:60',
        ];
    }
}
