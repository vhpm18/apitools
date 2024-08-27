<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Cache\CacheKey;
use App\Enums\Cache\CacheTtl;
use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Eloquent\Model;

final readonly class CacheService
{
    public function __construct(
        private CacheManager $cache,
    ) {}

    public function remember(CacheKey $key, CacheTtl $ttl, Closure $callback): mixed
    {
        return $this->cache->remember(
            key: $key->value,
            ttl: $ttl->value,
            callback: $callback,
        );
    }

    public function forget(CacheKey $key): mixed
    {
        return $this->cache->forget(key: $key->value);
    }

    public function getCache(CacheKey $key)
    {
        return $this->cache->get(key: $key->value);
    }

    public function updateCache(CacheKey $key, CacheTtl $ttl, Model $model): void
    {
        $cacheData = $this->cache->get(key: $key->value) ?? [];

        $cacheData[$model->id] = $model;
        $this->cache->put(
            key: CacheKey::UsersAll,
            data: $cacheData,
            ttl: $ttl->value
        );
        return;
    }
}
