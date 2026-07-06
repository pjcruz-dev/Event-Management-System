<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class StorageService
{
    public function disk(?string $disk = null): Filesystem
    {
        return Storage::disk($disk ?? (string) config('filesystems.default'));
    }

    public function store(
        UploadedFile $file,
        string $directory,
        ?string $disk = null,
        ?string $filename = null,
    ): string {
        $name = $filename ?? Str::uuid()->toString().'.'.$file->getClientOriginalExtension();

        $this->disk($disk)->putFileAs($directory, $file, $name);

        return trim($directory, '/').'/'.$name;
    }

    public function delete(string $path, ?string $disk = null): bool
    {
        return $this->disk($disk)->delete($this->toRelativePath($path));
    }

    public function url(string $path, ?string $disk = null): string
    {
        return $this->disk($disk)->url($this->toRelativePath($path));
    }

    public function exists(string $path, ?string $disk = null): bool
    {
        return $this->disk($disk)->exists($this->toRelativePath($path));
    }

    /**
     * Accepts a disk-relative path or a full /storage URL and returns the relative path.
     */
    public function toRelativePath(string $pathOrUrl): string
    {
        if (! str_starts_with($pathOrUrl, 'http://') && ! str_starts_with($pathOrUrl, 'https://')) {
            return ltrim($pathOrUrl, '/');
        }

        $path = parse_url($pathOrUrl, PHP_URL_PATH);
        if (! is_string($path)) {
            return ltrim($pathOrUrl, '/');
        }

        if (str_starts_with($path, '/storage/')) {
            return ltrim(substr($path, strlen('/storage/')), '/');
        }

        return ltrim($path, '/');
    }

    /**
     * @param  array<string, mixed>  $themeConfig
     * @return array<string, mixed>
     */
    public function normalizeThemeConfigPaths(array $themeConfig): array
    {
        foreach (['logo_url', 'hero_image_url', 'hero_video_url'] as $key) {
            if (isset($themeConfig[$key]) && is_string($themeConfig[$key]) && $themeConfig[$key] !== '') {
                $themeConfig[$key] = $this->toRelativePath($themeConfig[$key]);
            }
        }

        return $themeConfig;
    }
}
