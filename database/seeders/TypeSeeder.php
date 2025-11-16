<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        $types = [
            ['name' => 'Báo cáo', 'description' => 'Tài liệu dạng báo cáo', 'status' => 1],
            ['name' => 'Bài giảng', 'description' => 'Tài liệu dạng bài giảng', 'status' => 1],
            ['name' => 'Hướng dẫn', 'description' => 'Tài liệu dạng hướng dẫn', 'status' => 1],
            ['name' => 'Tham khảo', 'description' => 'Tài liệu tham khảo', 'status' => 1],
            ['name' => 'Khác', 'description' => 'Các loại tài liệu khác', 'status' => 0],
            ['name' => 'Luận văn', 'description' => 'Tài liệu dạng luận văn', 'status' => 1],
            ['name' => 'Đề thi', 'description' => 'Tài liệu đề thi và đề kiểm tra', 'status' => 1],
            ['name' => 'Báo cáo nghiên cứu', 'description' => 'Tài liệu báo cáo nghiên cứu', 'status' => 1],
            ['name' => 'Tài liệu tham khảo nâng cao', 'description' => 'Tài liệu tham khảo chuyên sâu', 'status' => 1],
            ['name' => 'Tài liệu hướng dẫn lab', 'description' => 'Hướng dẫn thực hành lab', 'status' => 1],
            ['name' => 'Slide', 'description' => 'Bài giảng dạng slide', 'status' => 1],
            ['name' => 'Video', 'description' => 'Tài liệu dạng video', 'status' => 1],
            ['name' => 'Đề thi thử', 'description' => 'Đề thi thử', 'status' => 0],
            ['name' => 'Bài tập', 'description' => 'Tài liệu dạng bài tập', 'status' => 1],
        ];

        foreach ($types as $type) {
            DB::table('types')->updateOrInsert(
                ['name' => $type['name']],
                array_merge($type, [
                    'created_at' => now(),
                    'updated_at' => now()
                ])
            );
        }
    }
}
