<?php

use App\Http\Controllers\Api\V1\Users\IndexController;
use Illuminate\Support\Facades\Route;


Route::get('/', IndexController::class)->name('users');
