<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        /* Role seeding */
        $roles = [
            [
                'role_id' => 10,
                'RoleName' => 'Admin',
                'RoleDescription' => 'selected user access admin role',
                'IsActive' => 1,
            ],
            [
                'role_id' => 11,
                'RoleName' => 'Customer',
                'RoleDescription' => 'Role Description',
                'IsActive' => 1,
            ],
            [
                'role_id' => 12,
                'RoleName' => 'Partner',
                'RoleDescription' => 'Role Description',
                'IsActive' => 1,
            ],
            [
                'role_id' => 14,
                'RoleName' => 'Lawyer',
                'RoleDescription' => 'Role Description',
                'IsActive' => 1,
            ],
        ];

        \App\Models\Role::insert($roles);
        /* /Role seeding */

        /* Case type seeding */
        $caseTypes = [
            [
                'CaseTypeID' => 1,
                'CaseTypeName' => 'Familienrecht',
                'Status' => 'Active',
            ],
            [
                'CaseTypeID' => 2,
                'CaseTypeName' => 'Familienrecht',
                'Status' => 'Active',
            ],
            [
                'CaseTypeID' => 3,
                'CaseTypeName' => 'Inkasso',
                'Status' => 'Active',
            ],
            [
                'CaseTypeID' => 4,
                'CaseTypeName' => 'Familienrecht',
                'Status' => 'Active',
            ],
        ];

        \App\Models\CasesType::insert($caseTypes);
        /* /Case type seeding */

        /* User seeding */
        $users = [
            [
                'role_id' => 10,
                'name' => 'David Valera',
                'first_name' => 'David',
                'last_name' => 'Valera',
                'email' => 'test@valera-melendez.com',
                'password' => Hash::make('1234567890'),
            ],
        ];

        \App\Models\User::insert($users);
        /* /User seeding */

        /* Permission seeding */
        $permissions = [
            [
                'user_id' => 1,
                'permission_id' => 1,
            ],
            [
                'user_id' => 1,
                'permission_id' => 2,
            ],
            [
                'user_id' => 1,
                'permission_id' => 3,
            ],
            [
                'user_id' => 1,
                'permission_id' => 4,
            ],
            [
                'user_id' => 1,
                'permission_id' => 5,
            ],
            [
                'user_id' => 1,
                'permission_id' => 6,
            ],
        ];

        \App\Models\Permissions::insert($permissions);
        /* /Permission seeding */
    }
}
