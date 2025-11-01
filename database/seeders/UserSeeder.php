<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    const MAX_RECORD = 50;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin Hệ thống',
            'status' => rand(0, 1),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        for ($i = 1; $i <= 5; $i++) {
            DB::table('users')->insert([
                'name' => 'Giảng viên ' . $i,
                'status' => rand(0, 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            DB::table('users')->insert([
                'name' => 'Sinh viên ' . $i,
                'status' => rand(0, 1),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
