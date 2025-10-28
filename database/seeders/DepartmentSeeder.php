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
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa công nghệ tự động',
                'description' => 'Khoa công nghệ tự động',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa động lực',
                'description' => 'Khoa Động lực',
                'status' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa đông phương',
                'description' => 'Khoa đông phương',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa cơ khí',
                'description' => 'Khoa cơ khí',
                'status' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa du lịch - khách sạn',
                'description' => 'Khoa du lịch - khách sạn',
                'status' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa điện - điển tử',
                'description' => 'Khoa điện - điển tử',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa khoa học cơ bản',
                'description' => 'Khoa khoa học cơ bản',
                'status' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa kinh tế',
                'description' => 'Khoa kinh tế',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Khoa tiếng anh',
                'description' => 'Khoa tiếng anh',
                'status' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
