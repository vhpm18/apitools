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
        $data = $request->validated([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        /** @var User $user */
        $user = User::whereEmail($data['email'])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        /** @var string */
        $userPassword = $user->password;
        $requestPassword = $request->string('password')->toString();


        if (!Hash::check($requestPassword, $userPassword)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'token' => $user
                ->createToken($data['token_name'])
                ->plainTextToken,
        ], Response::HTTP_CREATED);
    }
}
