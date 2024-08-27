<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, int $expiration = 3600): Response
    {
        $response = $next($request);

        $response->headers->add([
            'Cache-Control' => 'public, max-age=' . $expiration . ', ETag',
            'Pragma' => 'public',
            'Expires' => gmdate('D, d M Y H:i:s', time() + $expiration) . ' GMT',
        ]);

        return $response;
    }
}
