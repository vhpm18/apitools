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
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->add([
            'Cache-Control' => 'public, max-age=60, etag',
            'Pragma' => 'public',
            'Expires' => gmdate('D, d M Y H:i:s', time() + 60) . ' GMT',
        ]);

        return $response;
    }
}
