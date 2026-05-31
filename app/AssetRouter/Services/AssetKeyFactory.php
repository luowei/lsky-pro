<?php

namespace App\AssetRouter\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;

class AssetKeyFactory
{
    public function make(UploadedFile $file, string $sha256, ?Carbon $now = null): string
    {
        $now = $now ?: Carbon::now('UTC');
        $extension = $this->extension($file);

        return sprintf(
            'i/%s/%s/%s/%s%s',
            $now->format('Y'),
            $now->format('m'),
            $now->format('d'),
            $sha256,
            $extension ? ".{$extension}" : ''
        );
    }

    public function extension(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        if ($extension) {
            return $extension;
        }

        return match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
            default => '',
        };
    }
}
