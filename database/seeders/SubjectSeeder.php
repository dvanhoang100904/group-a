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
        // Danh sách môn theo từng khoa
        $subjectsByDepartment = [
            'Khoa Công Nghệ Thông Tin' => [
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
            ],
            'Khoa Kinh Tế' => [
                'Kinh tế vi mô',
                'Kinh tế vĩ mô',
                'Quản trị kinh doanh',
                'Marketing căn bản',
                'Du lịch - Khách sạn'
            ],
            'Khoa Khoa Học Cơ Bản' => [
                'Toán cao cấp',
                'Xác suất thống kê',
                'Vật lý đại cương',
                'Vật lý ứng dụng',
                'Hóa học đại cương',
                'Hóa học ứng dụng',
                'Sinh học cơ bản',
                'Khoa học môi trường'
            ],
            'Khoa Tiếng Anh' => [
                'Tiếng Anh chuyên ngành CNTT',
                'Tiếng Anh giao tiếp',
                'Tiếng Trung',
                'Tiếng Pháp',
                'Kỹ năng mềm',
                'Kỹ năng thuyết trình',
                'Kỹ năng lãnh đạo'
            ],
            // Nếu còn khoa khác, bạn có thể thêm tương tự
        ];

        $counter = 1;

        // Lấy tất cả khoa trong DB
        $departments = DB::table('departments')->get();

        foreach ($departments as $department) {
            $departmentName = $department->name;

            if (!isset($subjectsByDepartment[$departmentName])) continue;

            $availableSubjects = $subjectsByDepartment[$departmentName];

            // Chọn ngẫu nhiên 3–5 môn hoặc ít hơn nếu còn ít
            $subjectsCount = min(rand(3, 5), count($availableSubjects));
            $subjectsForDepartment = collect($availableSubjects)->shuffle()->take($subjectsCount);

            foreach ($subjectsForDepartment as $subjectName) {
                // Kiểm tra tránh trùng môn
                if (DB::table('subjects')->where('name', $subjectName)->exists()) continue;

                $code = 'MH' . str_pad($counter++, 3, '0', STR_PAD_LEFT);

                DB::table('subjects')->insert([
                    'name' => $subjectName,
                    'code' => $code,
                    'credits' => rand(2, 5),
                    'description' => "Môn $subjectName thuộc $departmentName",
                    'department_id' => $department->department_id,
                    'status' => 1, // active
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
