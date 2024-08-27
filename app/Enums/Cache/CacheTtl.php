<?php

declare(strict_types=1);

namespace App\Enums\Cache;

enum CacheTtl: int
{
    case OneMinute = 60;
    case fiveMinute = 30;
    case TenMinutes = 600;
    case TwentyMinutes = 1200;
    case ThirtyMinutes = 1800;
    case FortyMinutes = 2400;
    case FiftyMinutes = 3000;
    case OneHour = 3600;
}
