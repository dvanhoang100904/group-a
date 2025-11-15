<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    const MAX_RECORD = 100;

    public function run(): void
    {
        $adminRole = DB::table('roles')->where('name', 'Admin')->value('role_id');
        $teacherRole = DB::table('roles')->where('name', 'Giảng viên')->value('role_id');
        $studentRole = DB::table('roles')->where('name', 'Sinh viên')->value('role_id');

        DB::table('users')->insert([
            'name' => 'Admin Hệ thống',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('123456'),
            'status' => 1,
            'role_id' => $adminRole,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($i = 2; $i <= 10; $i++) {
            DB::table('users')->insert([
                'name' => 'Giảng viên ' . $i,
                'email' => "giangvien{$i}@gmail.com",
                'password' => Hash::make('123456'),
                'status' => rand(0, 1),
                'role_id' => $teacherRole,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        for ($i = 11; $i <= self::MAX_RECORD; $i++) {
            DB::table('users')->insert([
                'name' => 'Sinh viên ' . $i,
                'email' => "sinhvien{$i}@gmail.com",
                'password' => Hash::make('123456'),
                'status' => rand(0, 1),
                'role_id' => $studentRole,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
