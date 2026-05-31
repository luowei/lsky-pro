<?php

namespace App\AssetRouter\Services;

use Aws\S3\S3Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;

class AssetRouterStorage
{
    public function put(string $key, UploadedFile $file): array
    {
        if (config('asset-router.r2.enabled')) {
            return $this->putR2($key, $file);
        }

        return $this->putLocal($key, $file);
    }

    public function get(string $key): string
    {
        if (config('asset-router.r2.enabled')) {
            return $this->getR2($key);
        }

        return $this->getLocal($key);
    }

    public function delete(string $key): void
    {
        if (config('asset-router.r2.enabled')) {
            $this->deleteR2($key);
            return;
        }

        $this->deleteLocal($key);
    }

    private function putLocal(string $key, UploadedFile $file): array
    {
        $root = rtrim(config('asset-router.local_root'), DIRECTORY_SEPARATOR);
        $target = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $key);
        File::ensureDirectoryExists(dirname($target));
        File::copy($file->getRealPath(), $target);

        return [
            'provider' => 'local',
            'provider_key' => $key,
            'etag' => null,
        ];
    }

    private function getLocal(string $key): string
    {
        $root = rtrim(config('asset-router.local_root'), DIRECTORY_SEPARATOR);
        $target = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $key);

        if (! File::exists($target)) {
            throw new RuntimeException("Local asset not found: {$key}");
        }

        return File::get($target);
    }

    private function deleteLocal(string $key): void
    {
        $root = rtrim(config('asset-router.local_root'), DIRECTORY_SEPARATOR);
        $target = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $key);

        if (File::exists($target)) {
            File::delete($target);
        }
    }

    private function putR2(string $key, UploadedFile $file): array
    {
        $client = new S3Client([
            'credentials' => [
                'key' => config('asset-router.r2.access_key_id'),
                'secret' => config('asset-router.r2.secret_access_key'),
            ],
            'endpoint' => config('asset-router.r2.endpoint'),
            'region' => config('asset-router.r2.region'),
            'version' => '2006-03-01',
        ]);

        $result = $client->putObject([
            'Bucket' => config('asset-router.r2.bucket'),
            'Key' => $key,
            'Body' => fopen($file->getRealPath(), 'r'),
            'ContentType' => $file->getMimeType() ?: 'application/octet-stream',
        ]);

        return [
            'provider' => 'r2',
            'provider_key' => $key,
            'etag' => trim((string) ($result['ETag'] ?? ''), '"') ?: null,
        ];
    }

    private function getR2(string $key): string
    {
        $result = $this->r2Client()->getObject([
            'Bucket' => config('asset-router.r2.bucket'),
            'Key' => $key,
        ]);

        return (string) $result['Body'];
    }

    private function deleteR2(string $key): void
    {
        $this->r2Client()->deleteObject([
            'Bucket' => config('asset-router.r2.bucket'),
            'Key' => $key,
        ]);
    }

    private function r2Client(): S3Client
    {
        return new S3Client([
            'credentials' => [
                'key' => config('asset-router.r2.access_key_id'),
                'secret' => config('asset-router.r2.secret_access_key'),
            ],
            'endpoint' => config('asset-router.r2.endpoint'),
            'region' => config('asset-router.r2.region'),
            'version' => '2006-03-01',
        ]);
    }
}
