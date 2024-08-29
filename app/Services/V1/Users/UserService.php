<?php

declare(strict_types=1);

namespace App\Services\V1\Users;

use App\Enums\Cache\CacheKey;
use App\Enums\Cache\CacheTtl;
use App\Models\User;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;

final readonly class UserService
{
    public function __construct(
        private CacheService $cache,
        private DatabaseManager $database,
    ) {}

    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->cache->remember(
            key: CacheKey::UsersAll,
            ttl: CacheTtl::TenMinutes,
            callback: static fn() => User::query()->with('roles')->latest()->paginate($perPage),
        );
    }

    public function create(array $data): User|Model
    {
        try {
            return $this->database->transaction(
                callback: fn() => User::query()->create($data),
                attempts: 3,
            );
        } catch (\Exception $e) {
            // Manejo de excepciones
            throw new \RuntimeException('Error al crear el usuario: ' . $e->getMessage());
        }
    }
}
