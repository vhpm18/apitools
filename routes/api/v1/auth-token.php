<?php

declare(strict_types=1);

use App\Http\Controllers\AuthTokenController;
use App\Http\Controllers\RemoveTokenController;
use Illuminate\Support\Facades\Route;


Route::middleware(['guest'])->post('/login', AuthTokenController::class)->name('store');
Route::middleware(['auth:sanctum'])->post('/logout', RemoveTokenController::class)->name('destroy');
