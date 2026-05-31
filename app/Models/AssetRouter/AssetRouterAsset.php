<?php

namespace App\Models\AssetRouter;

use App\AssetRouter\Enums\AssetRouterVisibility;
use App\Models\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property int|null $owner_user_id
 * @property string $key
 * @property string $display_name
 * @property string $original_name
 * @property string $mime_type
 * @property string $extension
 * @property int $size_bytes
 * @property string|null $sha256
 * @property string|null $md5
 * @property int|null $width
 * @property int|null $height
 * @property string $visibility
 * @property string $asset_type
 * @property string $status
 * @property string $canonical_url
 * @property string|null $members_url
 * @property string $primary_provider
 */
class AssetRouterAsset extends Model
{
    use HasFactory, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'owner_user_id',
        'key',
        'display_name',
        'original_name',
        'mime_type',
        'extension',
        'size_bytes',
        'sha256',
        'md5',
        'width',
        'height',
        'visibility',
        'asset_type',
        'status',
        'canonical_url',
        'members_url',
        'primary_provider',
        'metadata',
        'created_by',
        'uploaded_ip',
    ];

    protected $casts = [
        'owner_user_id' => 'integer',
        'size_bytes' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'metadata' => 'collection',
    ];

    protected $appends = [
        'url',
        'links',
    ];

    protected static function booted()
    {
        static::creating(function (self $asset) {
            if (! $asset->id) {
                $asset->id = (string) Str::uuid();
            }
        });
    }

    public function url(): Attribute
    {
        return new Attribute(function () {
            return AssetRouterVisibility::isPublic($this->visibility)
                ? $this->canonical_url
                : ($this->members_url ?: $this->canonical_url);
        });
    }

    public function links(): Attribute
    {
        return new Attribute(fn () => collect([
            'url' => $this->url,
            'html' => "<img src=\"{$this->url}\" alt=\"{$this->display_name}\" />",
            'bbcode' => "[img]{$this->url}[/img]",
            'markdown' => "![{$this->display_name}]({$this->url})",
            'markdown_with_link' => "[![{$this->display_name}]({$this->url})]({$this->url})",
            'thumbnail_url' => $this->url,
        ]));
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id', 'id');
    }

    public function providerObjects(): HasMany
    {
        return $this->hasMany(AssetRouterProviderObject::class, 'asset_id', 'id');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(AssetRouterJob::class, 'asset_id', 'id');
    }
}
