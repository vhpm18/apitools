<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use JustSteveKing\Tools\Http\Enums\Status;
use Symfony\Component\HttpFoundation\Response;

final class AllowMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$methods): Response
    {
        if ( ! in_array($request->method(), $methods)) {
            return new JsonResponse(
                data: [
                    'Method Not Allowed',
                ],
                status: Status::BAD_REQUEST->value,
            );
        }
        return $next($request);
    }
}
