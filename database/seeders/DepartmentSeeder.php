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
        // Danh sách khoa
        $departments = [
            'Khoa Công Nghệ Thông Tin',
            'Khoa Công Nghệ Tự Động',
            'Khoa Động Lực',
            'Khoa Đông Phương',
            'Khoa Cơ Khí',
            'Khoa Du Lịch - Khách Sạn',
            'Khoa Điện - Điện Tử',
            'Khoa Khoa Học Cơ Bản',
            'Khoa Kinh Tế',
            'Khoa Tiếng Anh',
        ];

        foreach ($departments as $name) {
            DB::table('departments')->updateOrInsert(
                ['name' => $name], // kiểm tra tồn tại theo tên
                [
                    'description' => $name,
                    'status' => 1, // set 1 để tất cả khoa active mặc định
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
