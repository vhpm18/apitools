<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Resources\V1\UserResource;
use App\Http\Responses\ModelResponse;
use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;


final readonly class UserController
{
    public function __construct(
        private AuthManager $auth,
    ) {}

    public function __invoke(Request $request): Responsable
    {
        return new ModelResponse(
            data: new UserResource(
                resource: User::query()->with(
                    relations: ['roles.permissions'],
                )->where(
                    column: 'id',
                    operator: '=',
                    value: $this->auth->id(),
                )->firstOrFail(),
            )
        );
    }
}
