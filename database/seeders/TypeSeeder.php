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
            ['name' => 'Báo cáo', 'description' => 'Tài liệu dạng báo cáo', 'status' => true],
            ['name' => 'Bài giảng', 'description' => 'Tài liệu dạng bài giảng', 'status' => true],
            ['name' => 'Hướng dẫn', 'description' => 'Tài liệu dạng hướng dẫn', 'status' => true],
            ['name' => 'Tham khảo', 'description' => 'Tài liệu tham khảo', 'status' => true],
            ['name' => 'Khác', 'description' => 'Các loại tài liệu khác', 'status' => true],
            ['name' => 'Luận văn', 'description' => 'Tài liệu dạng luận văn', 'status' => true],
            ['name' => 'Đề thi', 'description' => 'Tài liệu đề thi và đề kiểm tra', 'status' => true],
            ['name' => 'Báo cáo nghiên cứu', 'description' => 'Tài liệu báo cáo nghiên cứu', 'status' => true],
            ['name' => 'Tài liệu tham khảo nâng cao', 'description' => 'Tài liệu tham khảo chuyên sâu', 'status' => true],
            ['name' => 'Tài liệu hướng dẫn lab', 'description' => 'Hướng dẫn thực hành lab', 'status' => true],

            // Thêm một số loại để test trạng thái inactive
            ['name' => 'Slide', 'description' => 'Bài giảng dạng slide', 'status' => true],
            ['name' => 'Video', 'description' => 'Tài liệu dạng video', 'status' => false],
            ['name' => 'Đề thi thử', 'description' => 'Đề thi thử', 'status' => false],
            ['name' => 'Bài tập', 'description' => 'Tài liệu dạng bài tập', 'status' => true],
        ];


        foreach ($types as $type) {
            DB::table('types')->insert(array_merge($type, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
}
