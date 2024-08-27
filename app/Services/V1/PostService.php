<?php

declare(strict_types=1);

namespace App\Services\V1;

use App\Enums\Cache\CacheKey;
use App\Enums\Cache\CacheTtl;
use App\Models\Post;
use App\Services\CacheService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;

final readonly class PostService
{
    public function __construct(
        private CacheService $cache,
        private DatabaseManager $database,
    ) {}

    public function all(int $perPage = 15): LengthAwarePaginator
    {
        return $this->cache->remember(
            key: CacheKey::PostAll,
            ttl: CacheTtl::TenMinutes,
            callback: static fn() => Post::query()->with('relations')->latest()->paginate($perPage),
        );
    }

    public function create(array $data): Post|Model
    {
        try {
            return $this->database->transaction(
                callback: fn() => Post::query()->create($data),
                attempts: 3,
            );
        } catch (\Exception $e) {
            // Manejo de excepciones
            throw new \RuntimeException('Error al crear el usuario: ' . $e->getMessage());
        }
    }
}
