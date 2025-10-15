<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        DB::table('departments')->insert([
            [
                'name' => 'Khoa công nghệ thông tin',
                'description' => 'Khoa công nghệ thông tin',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa công nghệ tự động',
                'description' => 'Khoa công nghệ tự động',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa động lực',
                'description' => 'Khoa Động lực',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa đông phương',
                'description' => 'Khoa đông phương',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa cơ khí',
                'description' => 'Khoa cơ khí',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa du lịch - khách sạn',
                'description' => 'Khoa du lịch - khách sạn',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa điện - điển tử',
                'description' => 'Khoa điện - điển tử',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa khoa học cơ bản',
                'description' => 'Khoa khoa học cơ bản',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa kinh tế',
                'description' => 'Khoa kinh tế',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa tiếng anh',
                'description' => 'Khoa tiếng anh',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
