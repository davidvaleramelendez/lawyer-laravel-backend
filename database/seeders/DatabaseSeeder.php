<?php

namespace Database\Seeders;

use App\Models\CasesType;
use App\Models\PdfApi;
use App\Models\Permissions;
use App\Models\PlacetelCallApiToken;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
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
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'role_id' => 11,
                'RoleName' => 'Customer',
                'RoleDescription' => 'Role Description',
                'IsActive' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'role_id' => 12,
                'RoleName' => 'Partner',
                'RoleDescription' => 'Role Description',
                'IsActive' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'role_id' => 14,
                'RoleName' => 'Lawyer',
                'RoleDescription' => 'Role Description',
                'IsActive' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        Role::insert($roles);
        /* /Role seeding */

        /* Case type seeding */
        $caseTypes = [
            [
                'CaseTypeID' => 1,
                'CaseTypeName' => 'Familienrecht',
                'Status' => 'Active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'CaseTypeID' => 2,
                'CaseTypeName' => 'Familienrecht',
                'Status' => 'Active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'CaseTypeID' => 3,
                'CaseTypeName' => 'Inkasso',
                'Status' => 'Active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'CaseTypeID' => 4,
                'CaseTypeName' => 'Familienrecht',
                'Status' => 'Active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        CasesType::insert($caseTypes);
        /* /Case type seeding */

        /* User seeding */
        $users = [
            [
                'role_id' => 10,
                'name' => 'David Valera',
                'first_name' => 'David',
                'last_name' => 'Valera',
                'email' => 'test@valera-melendez.de',
                'password' => Hash::make('1234567890'),
                'Contact' => "0000000000",
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        User::insert($users);
        /* /User seeding */

        /* Permission seeding */
        $permissions = [
            ['user_id' => 1, 'permission_id' => 1, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 2, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 3, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 4, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 5, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 6, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 7, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 8, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 9, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 10, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 11, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 12, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 13, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 14, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 15, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 16, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['user_id' => 1, 'permission_id' => 17, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ];

        Permissions::insert($permissions);
        /* /Permission seeding */

        /* Pdf Api */
        $pdfApis = [
            'key' => "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiIxIiwianRpIjoiYWM3ODU3YjlkODZhNmMzOWQ4NDIyYWZiZWI4YjNlZWYyMWI4MjgyMjJmOTAyZTU4NTI4YmFmNTgxYjk4OTM3M2I4Y2JhNWNjN2I4ZGJhOTkiLCJpYXQiOjE2NTkzNTA0OTguNjkyMTA3LCJuYmYiOjE2NTkzNTA0OTguNjkyMTA5LCJleHAiOjQ4MTUwMjQwOTguNjg3MjY4LCJzdWIiOiI1NDUzNDg2NCIsInNjb3BlcyI6WyJ1c2VyLnJlYWQiLCJ1c2VyLndyaXRlIiwidGFzay5yZWFkIiwidGFzay53cml0ZSIsIndlYmhvb2sucmVhZCIsIndlYmhvb2sud3JpdGUiLCJwcmVzZXQucmVhZCIsInByZXNldC53cml0ZSJdfQ.n3kzFYSiDzSjkvzNduyWe-ZoaSucgTylpclP2O0oI4NKrA9Vd0qU6FAnOYq8MOK25MqbxnR-mkvQAGzNjVHCFv4xnWQq9lGzbs6N_ReEnjZbKi2Dntflr5KhYOu4NRo6EsW3Ko2p6dEBPqOowvh5BC7EGCmFlpHfXBtJ9vtLKXR-unfRdQLM-Zh6noYMf63uN8LvD71WRKUDJBchA4-7rrNIM5Fl0roR7SAZ1tHtBSu5tdnRMOgVomLoq3BGdWC6pWsI90IjeqjrORWQHM-kJez0__uytHo-2ohlTUWdzWB68Fq7To-VGXoiNPQSiPnaE8aPI1f_o8Tu28ngyedDcNKRPLYwkf1XmI5J_aGRsoMqBcqwEofSpPWOkLoJKqCwdpRGHdqckg4ImQ-9ACvQILS_QJYLB4i4_UmDy7sgGQ5Xo6MQl37WZKQOWw1bPkn9mNnoZNVAb_qmnCUHV6btdIxAlMN7JPZ-i1JbSoR6bBkb78RwxsNLqwrvE9EfNb-qyt6yfWyE5l5l6hNlqam06DYCjqrY-2Ev2EiVOr_v4ML3EtaXcPgOw0YFG_gf26D4MxKrlWaiu7KUmop2VtSo3HXSmtk6gXWxZkgcmNhP_Gf3P4Den3opsXbOAK7mWkwswxFsXKk0hh1TTdwrwKtxzlfCt496rQ6HCZKu-tbr4g4",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        PdfApi::insert($pdfApis);
        /* /Pdf Api */

        /* Placetel Call Api Token */
        $placetelCallApiToken = [
            'token' => "ec520ff06bbc287117441de7f27220e9e5f0c947e872392f1ab3cb6529ec0de16be80dfed63d439a3ad7401d0ca068765b7c1ebf1cfcd3842dfa1b1c8d0374c8",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        PlacetelCallApiToken::insert($placetelCallApiToken);
        /* /Placetel Call Api Token */
    }
}
