<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\UserResource;
use App\Http\Responses\CollectionResponse;
use App\Http\Responses\V1\PaginateResponse;
use App\Models\User;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Responsable
    {
        $users = User::query()->with('roles')->latest()->where('username', '!=', 'ines-marvin')
            ->paginate();

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
