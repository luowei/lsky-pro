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
            'file' => 'required_without_all:image,source,smfile|file|max:51200',
            'image' => 'nullable|file|max:51200',
            'source' => 'nullable|file|max:51200',
            'smfile' => 'nullable|file|max:51200',
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
