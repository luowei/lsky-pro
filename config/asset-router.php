<?php

return [
    'enabled' => env('ASSET_ROUTER_ENABLED', true),
    'default_visibility' => env('ASSET_ROUTER_DEFAULT_VISIBILITY', 'public'),
    'public_base_url' => env('ASSET_PUBLIC_BASE_URL', 'https://assets.markdev.work'),
    'members_base_url' => env('MEMBERS_ASSET_BASE_URL', 'https://assets.markdev.work/m'),
    'local_root' => env('ASSET_ROUTER_LOCAL_ROOT') ?: storage_path('app/asset-router'),
    'r2' => [
        'enabled' => env('ASSET_ROUTER_R2_ENABLED', false),
        'endpoint' => env('R2_ENDPOINT'),
        'region' => env('R2_REGION', 'auto'),
        'bucket' => env('R2_BUCKET', 'second-brain-assets-prod'),
        'access_key_id' => env('R2_ACCESS_KEY_ID'),
        'secret_access_key' => env('R2_SECRET_ACCESS_KEY'),
    ],
    'github' => [
        'repo' => env('GITHUB_ASSET_REPO', 'luowei/second-brain-image-assets'),
        'branch' => env('GITHUB_ASSET_BRANCH', 'main'),
        'token' => env('GITHUB_ASSET_TOKEN'),
        'commit_author_name' => env('GITHUB_ASSET_COMMIT_AUTHOR_NAME', 'lsky-pro asset router'),
        'commit_author_email' => env('GITHUB_ASSET_COMMIT_AUTHOR_EMAIL', 'asset-router@markdev.work'),
        'jsdelivr_base_url' => env('GITHUB_JSDELIVR_BASE_URL', 'https://cdn.jsdelivr.net/gh/luowei/second-brain-image-assets@main'),
    ],
    'mirror' => [
        'auto_dispatch' => env('ASSET_ROUTER_MIRROR_AUTO_DISPATCH', false),
        'queue' => env('ASSET_ROUTER_MIRROR_QUEUE', 'asset-router'),
    ],
    'second_brain' => [
        'sync_url' => env('ASSET_ROUTER_SECOND_BRAIN_SYNC_URL'),
        'sync_token' => env('ASSET_ROUTER_SECOND_BRAIN_SYNC_TOKEN'),
    ],
];
