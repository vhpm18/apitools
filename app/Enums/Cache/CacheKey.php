<?php

declare(strict_types=1);

namespace App\Enums\Cache;

enum CacheKey: string
{
    case UsersAll = 'users:all';
    case PostAll = 'post:all';
    case CommentAll = 'comment:all';
}
