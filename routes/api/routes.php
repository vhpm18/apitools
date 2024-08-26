<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->as('v1:')
    ->group(
        base_path('routes/api/v1/routes.php')
    );




// Route::get('/user', function (Request $request) {
//     return response()->json(User::all());
//     //return $request->user();
// })->middleware('treblle', 'allow:GET.POST,PUT');
// //->middleware('auth:sanctum');
