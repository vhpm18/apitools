<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RemoveTokenController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User */
        $user = $request->user();
        /** @var PersonalAccessToken */
        $token = $user->currentAccessToken();

        $token->delete();

        return response()->noContent();
    }
}
