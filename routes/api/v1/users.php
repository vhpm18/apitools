<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Users\IndexController;
use Illuminate\Support\Facades\Route;


Route::get('/', IndexController::class)->name('index')
    ->middleware([
        'permission:users.list',
        'cache_header:60'
    ]);
