<?php

namespace App\AssetRouter\Enums;

class AssetRouterVisibility
{
    public const Public = 'public';
    public const Members = 'members';
    public const Private = 'private';

    public static function normalize(?string $value): string
    {
        return in_array($value, [self::Public, self::Members, self::Private], true)
            ? $value
            : self::Public;
    }

    public static function isPublic(string $value): bool
    {
        return $value === self::Public;
    }
}
