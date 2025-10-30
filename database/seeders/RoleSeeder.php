<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $roles = ['Admin', 'Giảng viên', 'Sinh viên'];
        foreach ($roles as $roleName) {
            DB::table('roles')->insert([
                'name' => $roleName,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
