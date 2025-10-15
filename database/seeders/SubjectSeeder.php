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
            'Lập trình PHP',
            'Lập trình Java',
            'Cơ sở dữ liệu',
            'Mạng máy tính',
            'Toán cao cấp',
            'Vật lý',
            'Hóa học',
            'Tiếng Anh',
            'Kinh tế',
            'Du lịch'
        ];

        $departments = DB::table('departments')->get();

        $assignedSubjects = [];

        foreach ($departments as $department) {

            $availableSubjects = array_diff($sampleSubjects, $assignedSubjects);
            $subjectsCount = min(rand(3, 5), count($availableSubjects));

            if ($subjectsCount == 0) continue;

            $subjectsForDepartment = collect($availableSubjects)->shuffle()->take($subjectsCount);

            foreach ($subjectsForDepartment as $subjectName) {
                DB::table('subjects')->insert([
                    'name' => $subjectName,
                    'description' => "Môn $subjectName thuộc {$department->name}",
                    'department_id' => $department->department_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $assignedSubjects[] = $subjectName;
            }
        }
    }
}
