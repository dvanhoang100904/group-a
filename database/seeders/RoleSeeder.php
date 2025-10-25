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

    const MAX_RECORD = 5;

    public function run(): void
    {
        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            for ($i = 1; $i <= self::MAX_RECORD; $i++) {
                DB::table('roles')->insert([
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }
}
