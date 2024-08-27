<?php

namespace App\Observers\V1;

use App\Enums\Cache\CacheKey;
use App\Enums\Cache\CacheTtl;
use App\Models\User;
use App\Services\CacheService;

class UserObserver
{

    public function __construct(
        private CacheService $cache
    ) {}
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $this->cache->updateCache(CacheKey::UsersAll, CacheTtl::TenMinutes, $user);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $this->cache->updateCache(CacheKey::UsersAll, CacheTtl::TenMinutes, $user);
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        $this->cache->updateCache(CacheKey::UsersAll, CacheTtl::TenMinutes, $user);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        $this->cache->updateCache(CacheKey::UsersAll, CacheTtl::TenMinutes, $user);
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        $this->cache->updateCache(CacheKey::UsersAll, CacheTtl::TenMinutes, $user);
    }
}
