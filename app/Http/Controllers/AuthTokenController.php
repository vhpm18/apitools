<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthTokenController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(AuthTokenRequest $request): JsonResponse
    {
        $data = $request->validated();

        /** @var User $user */
        $user = User::whereEmail($data['email'])->first();

        if (! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [(string) trans('validation.credentials')],
            ]);
        }

        return response()->json([
            'token' => $user
                ->createToken($data['token_name'])
                ->plainTextToken,
        ], Response::HTTP_CREATED);
    }
}
