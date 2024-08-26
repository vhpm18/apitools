<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class CreateAuthorizationTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->uuid('id');
            $table->timestamps();
            $table->string('name')->unique()->index();
            $table->string('label')->nullable();
        });

        Schema::create('permissions', function (Blueprint $table): void {
            $table->uuid('id');
            $table->timestamps();
            $table->string('name')->unique()->index();
            $table->string('label')->nullable();
        });

        Schema::create('permission_role', function (Blueprint $table): void {
            $table->uuid('permission_id');
            $table->uuid('role_id');

            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('permission_user', function (Blueprint $table): void {
            $table->uuid('permission_id');
            $table->uuid('user_id');

            $table->primary(['permission_id', 'user_id']);
        });

        Schema::create('role_user', function (Blueprint $table): void {
            $table->uuid('role_id');
            $table->uuid('user_id');

            $table->primary(['role_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('role_user');
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permission_user');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
}
