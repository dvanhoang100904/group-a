<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    const MAX_RECORD = 100;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            DB::table('users')->insert([
                'name' => 'User ' . $i,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
