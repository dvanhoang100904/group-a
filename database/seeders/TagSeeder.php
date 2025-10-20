<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => 'Quan trọng', 'description' => 'Tài liệu quan trọng cần lưu ý'],
            ['name' => 'Mới', 'description' => 'Tài liệu mới cập nhật'],
            ['name' => 'Hướng dẫn', 'description' => 'Tài liệu hướng dẫn sử dụng'],
            ['name' => 'Báo cáo', 'description' => 'Tài liệu dạng báo cáo'],
            ['name' => 'Tham khảo', 'description' => 'Tài liệu tham khảo'],
            ['name' => 'Môn học', 'description' => 'Tài liệu liên quan môn học'],
            ['name' => 'Kinh tế', 'description' => 'Tài liệu ngành kinh tế'],
            ['name' => 'Công nghệ', 'description' => 'Tài liệu ngành công nghệ'],
            ['name' => 'Luận văn', 'description' => 'Tài liệu dạng luận văn'],
            ['name' => 'Nghiên cứu', 'description' => 'Tài liệu nghiên cứu khoa học'],
        ];

        foreach ($tags as $tag) {
            DB::table('tags')->insert(array_merge($tag, [
                'created_at' => now(),
                'updated_at' => now()
            ]));
        }

        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $tagIds = DB::table('tags')->pluck('tag_id')->toArray();

        foreach ($documents as $documentId) {
            // Mỗi tài liệu sẽ có 1-3 tag ngẫu nhiên
            $randomTags = collect($tagIds)->shuffle()->take(rand(1, 3))->toArray();

            foreach ($randomTags as $tagId) {
                // Kiểm tra trước khi insert để tránh duplicate
                if (!DB::table('document_tags')->where([
                    'document_id' => $documentId,
                    'tag_id' => $tagId
                ])->exists()) {
                    DB::table('document_tags')->insert([
                        'document_id' => $documentId,
                        'tag_id' => $tagId,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }
}
