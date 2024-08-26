<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        $user = User::factory()->create([
            'name' => 'VictorH Pereira',
            'email' => 'vhpm18@gmail.com',
        ]);

        $admin = Role::query()->create([
            'name' => 'administrator',
            'label' => 'Admin',
        ]);

        $member = Role::query()->create([
            'name' => 'member',
            'label' => 'Member',
        ]);

        $createUsers = Permission::create([
            'name' => 'users.create',
            'label' => 'Create Users',
        ]);

        $deleteUsers = Permission::create([
            'name' => 'users.delete',
            'label' => 'Delete Users',
        ]);

        $listUsers = Permission::create([
            'name' => 'users.list',
            'label' => 'List Users',
        ]);

        $admin->permissions()->save($createUsers);
        $admin->permissions()->save($listUsers);
        $admin->permissions()->save($deleteUsers);
        $member->permissions()->save($listUsers);

        $user->roles()->save($admin);
    }
}
