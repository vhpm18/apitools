<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'App.Model.User.{id}',
    fn(User $user, string $id): bool =>  $user->getKey() ===  $id,
);
