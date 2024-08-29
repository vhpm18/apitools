<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RemoveTokenController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        /** @var User */
        $user = $request->user();
        /** @var PersonalAccessToken */
        $token = $user->currentAccessToken();

        $token->delete();

        return response()->noContent();
    }
}
