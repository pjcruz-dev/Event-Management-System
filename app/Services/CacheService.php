<?php

declare(strict_types=1);

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

final class CacheService
{
    public function prefixKey(string $key, ?int $organizationId = null): string
    {
        if ($organizationId === null) {
            return $key;
        }

        return sprintf('org:%d:%s', $organizationId, $key);
    }

    public function get(string $key, mixed $default = null, ?int $organizationId = null): mixed
    {
        return Cache::get($this->prefixKey($key, $organizationId), $default);
    }

    public function put(string $key, mixed $value, int $ttlSeconds, ?int $organizationId = null): bool
    {
        return Cache::put($this->prefixKey($key, $organizationId), $value, $ttlSeconds);
    }

    public function remember(string $key, int $ttlSeconds, Closure $callback, ?int $organizationId = null): mixed
    {
        return Cache::remember($this->prefixKey($key, $organizationId), $ttlSeconds, $callback);
    }

    public function forget(string $key, ?int $organizationId = null): bool
    {
        return Cache::forget($this->prefixKey($key, $organizationId));
    }
}
