<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Http\Responses\PaginateResponse;
use App\Services\UserService;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexController extends Controller
{

    public function __construct(
        private UserService $service,
    ) {}

    public function __invoke(Request $request): Responsable
    {
        try {
            $perPage = $request->get('per_page', 15); // Valor por defecto
            $users = $this->service->all($perPage); // Pasar el valor de perPage
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al recuperar usuarios'], 500);
        }

        return new PaginateResponse(
            new LengthAwarePaginator(
                UserResource::collection($users),
                total: $users->total(),
                perPage: 15,
                currentPage: $request->get('page', 1)
            )
        );
    }
}
