<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\UserController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('user', UserController::class)->name('user');

    Route::prefix('users')->as('users:')
        ->group(
            base_path('routes/api/v1/users.php')
        );
});

Route::middleware([])->group(
    base_path('routes/api/v1/auth.php'),
);
