<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthTokenController;
use App\Http\Controllers\Api\V1\Auth\RemoveTokenController;
use Illuminate\Support\Facades\Route;




Route::prefix('{version}')
    ->group(
        base_path('routes/api/v1/routes.php')
    );


Route::middleware(['guest'])
    ->post('/auth/token', AuthTokenController::class)
    ->name('api.auth.token');

Route::middleware('auth:sanctum')
    ->post('/token/remove', RemoveTokenController::class)
    ->name('api.token.remove');
