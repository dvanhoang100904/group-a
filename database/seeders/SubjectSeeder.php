<?php

namespace Database\Seeders;

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
            'Du lịch - Khách sạn',
        ];

        $departments = DB::table('departments')->get();
        $counter = 1; // Dùng để sinh mã môn học tự động

        foreach ($departments as $department) {
            $availableSubjects = array_diff($sampleSubjects, DB::table('subjects')->pluck('name')->toArray());
            $subjectsCount = min(rand(3, 5), count($availableSubjects));

            if ($subjectsCount == 0) continue;

            $subjectsForDepartment = collect($availableSubjects)->shuffle()->take($subjectsCount);

            foreach ($subjectsForDepartment as $subjectName) {
                if (!DB::table('subjects')->where('name', $subjectName)->exists()) {

                    // Sinh mã môn học: MH001, MH002, ...
                    $code = 'MH' . str_pad($counter++, 3, '0', STR_PAD_LEFT);

                    DB::table('subjects')->insert([
                        'code' => $code,
                        'name' => $subjectName,
                        'credits' => rand(2, 5), // số tín chỉ ngẫu nhiên 2–5
                        'description' => "Môn $subjectName thuộc {$department->name}",
                        'department_id' => $department->department_id,
                        'status' => rand(0, 1),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
