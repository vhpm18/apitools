<?php

declare(strict_types=1);

use \App\Http\Middleware\CacheLayer;
use App\Http\Middleware\AllowMiddleware;
use App\Http\Middleware\CacheHeadersMiddleware;
use App\Http\Middleware\ContentTypeMiddleware;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\Security\XFrameOptionsMiddleware;
use App\Http\Responses\ApiError;
use App\Http\Responses\ErrorResponse;
use DirectoryTree\Authorization\Middleware\PermissionMiddleware;
use DirectoryTree\Authorization\Middleware\RoleMiddleware;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use JustSteveKing\Tools\Http\Enums\Status;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Treblle\Middlewares\TreblleMiddleware;
use Treblle\SecurityHeaders\Http\Middleware\CertificateTransparencyPolicy;
use Treblle\SecurityHeaders\Http\Middleware\ContentTypeOptions;
use Treblle\SecurityHeaders\Http\Middleware\PermissionsPolicy;
use Treblle\SecurityHeaders\Http\Middleware\RemoveHeaders;
use Treblle\SecurityHeaders\Http\Middleware\SetReferrerPolicy;
use Treblle\SecurityHeaders\Http\Middleware\StrictTransportSecurity;







return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api/routes.php',
        commands: __DIR__ . '/../routes/console/routes.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->api(append: [
            RemoveHeaders::class,
            StrictTransportSecurity::class,
            SetReferrerPolicy::class,
            PermissionsPolicy::class,
            ContentTypeOptions::class,
            CertificateTransparencyPolicy::class,
            XFrameOptionsMiddleware::class,
            ContentTypeMiddleware::class,
            EnsureFrontendRequestsAreStateful::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            CacheLayer::class,
        ]);
        $middleware->alias([
            'allow' => AllowMiddleware::class,
            'treblle' => TreblleMiddleware::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'verified' => EnsureEmailIsVerified::class,
            'cache_header' => CacheHeadersMiddleware::class
        ]);
        if ('redis' === getenv('CACHE_STORE')) {
            $middleware->throttleWithRedis();
        }
    })
    ->withBroadcasting(
        __DIR__ . '/../routes/sockets/routes.php',
        ['prefix' => 'api', 'middleware' => ['api', 'auth:sanctum']],
    )
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(fn(ModelNotFoundException $exception, Request $request)
        => new ErrorResponse(
            data: new ApiError(
                title: 'Not Found',
                detail: $exception->getMessage(),
                instance: $request->path(),
                code: $exception->getCode(),
                link: 'https://docs.domain.com/errors/not-found',
            ),
            status: Status::NOT_FOUND,
        ));
    })->create();
