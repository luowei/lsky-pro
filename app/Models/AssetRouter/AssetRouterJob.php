<?php

namespace App\Models\AssetRouter;

use App\Models\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AssetRouterJob extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'asset_id',
        'type',
        'status',
        'payload',
        'result',
        'attempts',
        'last_error',
    ];

    protected $casts = [
        'payload' => 'collection',
        'result' => 'collection',
        'attempts' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (self $job) {
            if (! $job->id) {
                $job->id = (string) Str::uuid();
            }
        });
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(AssetRouterAsset::class, 'asset_id', 'id');
    }
}
