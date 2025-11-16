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

    const TAGS_PER_DOC_MIN = 2;
    const TAGS_PER_DOC_MAX = 5;

    public function run(): void
    {
        $tags = [
            ['name' => 'Quan trọng', 'description' => 'Tài liệu quan trọng cần lưu ý', 'status' => 1],
            ['name' => 'Mới', 'description' => 'Tài liệu mới cập nhật', 'status' => 1],
            ['name' => 'Hướng dẫn', 'description' => 'Tài liệu hướng dẫn sử dụng', 'status' => 1],
            ['name' => 'Báo cáo', 'description' => 'Tài liệu dạng báo cáo', 'status' => 1],
            ['name' => 'Tham khảo', 'description' => 'Tài liệu tham khảo', 'status' => 1],
            ['name' => 'Môn học', 'description' => 'Tài liệu liên quan môn học', 'status' => 1],
            ['name' => 'Kinh tế', 'description' => 'Tài liệu ngành kinh tế', 'status' => 1],
            ['name' => 'Công nghệ', 'description' => 'Tài liệu ngành công nghệ', 'status' => 1],
            ['name' => 'Luận văn', 'description' => 'Tài liệu dạng luận văn', 'status' => 1],
            ['name' => 'Nghiên cứu', 'description' => 'Tài liệu nghiên cứu khoa học', 'status' => 1],
            ['name' => 'Đề thi', 'description' => 'Tài liệu dạng đề thi', 'status' => 1],
            ['name' => 'Bản nháp', 'description' => 'Tài liệu chưa được duyệt', 'status' => 0],
            ['name' => 'Đã duyệt', 'description' => 'Tài liệu đã được duyệt', 'status' => 1],
            ['name' => 'Lab', 'description' => 'Tài liệu hướng dẫn thực hành', 'status' => 1],
            ['name' => 'Video', 'description' => 'Tài liệu dạng video', 'status' => 1],
        ];

        // Insert tags, tránh duplicate
        foreach ($tags as $tag) {
            DB::table('tags')->updateOrInsert(
                ['name' => $tag['name']],
                array_merge($tag, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // Lấy document_id và tag_id
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $tagIds = DB::table('tags')->pluck('tag_id')->toArray();

        $documentTagsMap = [];

        foreach ($documents as $documentId) {
            $randomTags = collect($tagIds)
                ->shuffle()
                ->take(rand(self::TAGS_PER_DOC_MIN, self::TAGS_PER_DOC_MAX))
                ->toArray();

            foreach ($randomTags as $tagId) {
                $key = $documentId . '-' . $tagId; // tránh duplicate
                $documentTagsMap[$key] = [
                    'document_id' => $documentId,
                    'tag_id' => $tagId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Bulk insert theo chunk 500
        foreach (array_chunk(array_values($documentTagsMap), 500) as $chunk) {
            DB::table('document_tags')->insert($chunk);
        }
    }
}
