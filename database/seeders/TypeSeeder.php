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
            ['name' => 'Báo cáo', 'description' => 'Tài liệu dạng báo cáo'],
            ['name' => 'Bài giảng', 'description' => 'Tài liệu dạng bài giảng'],
            ['name' => 'Hướng dẫn', 'description' => 'Tài liệu dạng hướng dẫn'],
            ['name' => 'Tham khảo', 'description' => 'Tài liệu tham khảo'],
            ['name' => 'Khác', 'description' => 'Các loại tài liệu khác'],
            ['name' => 'Luận văn', 'description' => 'Tài liệu dạng luận văn'],
            ['name' => 'Đề thi', 'description' => 'Tài liệu đề thi và đề kiểm tra'],
            ['name' => 'Báo cáo nghiên cứu', 'description' => 'Tài liệu báo cáo nghiên cứu'],
            ['name' => 'Tài liệu tham khảo nâng cao', 'description' => 'Tài liệu tham khảo chuyên sâu'],
            ['name' => 'Tài liệu hướng dẫn lab', 'description' => 'Hướng dẫn thực hành lab'],
        ];

        foreach ($types as $type) {
            DB::table('types')->insert(array_merge($type, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }
    }
}
