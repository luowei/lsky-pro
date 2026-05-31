<?php

namespace App\Models\AssetRouter;

use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetRouterProviderObject extends Model
{
    protected $fillable = [
        'asset_id',
        'provider',
        'provider_key',
        'url',
        'status',
        'etag',
        'last_checked_at',
        'last_error',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(AssetRouterAsset::class, 'asset_id', 'id');
    }
}
