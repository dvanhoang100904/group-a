<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $sampleSubjects = [
            // Lập trình & Công nghệ
            'Lập trình PHP',
            'Lập trình Java',
            'Lập trình Python',
            'Phát triển Web Frontend',
            'Phát triển Web Backend',
            'Cơ sở dữ liệu',
            'Mạng máy tính',
            'An ninh mạng',
            'Trí tuệ nhân tạo',
            'Học máy (Machine Learning)',

            // Toán & Khoa học tự nhiên
            'Toán cao cấp',
            'Xác suất thống kê',
            'Vật lý đại cương',
            'Vật lý ứng dụng',
            'Hóa học đại cương',
            'Hóa học ứng dụng',
            'Sinh học cơ bản',
            'Khoa học môi trường',

            // Ngoại ngữ & Kỹ năng
            'Tiếng Anh chuyên ngành CNTT',
            'Tiếng Anh giao tiếp',
            'Tiếng Trung',
            'Tiếng Pháp',
            'Kỹ năng mềm',
            'Kỹ năng thuyết trình',
            'Kỹ năng lãnh đạo',

            // Kinh tế & Quản lý
            'Kinh tế vi mô',
            'Kinh tế vĩ mô',
            'Quản trị kinh doanh',
            'Marketing căn bản',
            'Du lịch - Khách sạn'
        ];

        $departments = DB::table('departments')->get();

        $assignedSubjects = [];

        foreach ($departments as $department) {
            $availableSubjects = array_diff($sampleSubjects, DB::table('subjects')->pluck('name')->toArray());
            $subjectsCount = min(rand(3, 5), count($availableSubjects));

            if ($subjectsCount == 0) continue;

            $subjectsForDepartment = collect($availableSubjects)->shuffle()->take($subjectsCount);

            foreach ($subjectsForDepartment as $subjectName) {
                // Check trước khi insert
                if (!DB::table('subjects')->where('name', $subjectName)->exists()) {
                    DB::table('subjects')->insert([
                        'name' => $subjectName,
                        'description' => "Môn $subjectName thuộc {$department->name}",
                        'department_id' => $department->department_id,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }
}
