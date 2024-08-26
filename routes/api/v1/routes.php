<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'treblle'])->group(function () {
    Route::prefix('users')->as('users:')
        ->group(
            base_path('routes/api/v1/users.php')
        );
});

Route::middleware([])->group(
    base_path('routes/api/v1/auth.php'),
);
