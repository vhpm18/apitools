<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Observers\V1\UserObserver;
use DirectoryTree\Authorization\Authorization;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict();
        JsonResource::withoutWrapping();

        ResetPassword::createUrlUsing(
            fn(object $notifiable, string $token) =>
            config('app.frontend_url') . "/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}"
        );

        Authorization::useUserModel(User::class);
        Authorization::useRoleModel(Role::class);
        Authorization::usePermissionModel(Permission::class);

        Authorization::cacheKey('auth:');

        //register observer
        User::observe(UserObserver::class);

        if ($this->app->environment('local')) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }
}
