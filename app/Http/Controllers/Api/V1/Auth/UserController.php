<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\Version;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Responses\ModelResponse;
use App\Models\User;
use Illuminate\Auth\AuthManager;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


final readonly class UserController
{
    public function __construct(
        private AuthManager $auth,
    ) {}

    public function __invoke(Request $request, Version $version): Responsable
    {
        abort_unless(
            $version->greaterThanOrEqualsTo(Version::v1_0),
            Response::HTTP_NOT_FOUND
        );
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
