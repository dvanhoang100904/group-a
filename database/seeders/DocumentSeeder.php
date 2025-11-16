<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    const MAX_RECORD = 1000;
    const TAGS_PER_DOC_MIN = 1;
    const TAGS_PER_DOC_MAX = 3;

    public function run(): void
    {
        $users = DB::table('users')->pluck('user_id')->toArray();
        $folders = DB::table('folders')->pluck('folder_id')->toArray();
        $types = DB::table('types')->pluck('type_id', 'name')->toArray();
        $subjects = DB::table('subjects')->pluck('subject_id', 'name')->toArray();
        $tags = DB::table('tags')->pluck('tag_id')->toArray();

        if (empty($users) || empty($folders) || empty($types) || empty($subjects) || empty($tags)) {
            return; // Tránh lỗi nếu table rỗng
        }

        $statuses = ['private', 'public', 'restricted'];
        $shareModes = ['private', 'public', 'custom'];

        $documents = [];
        $documentTags = [];

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            // Lấy type và subject ngẫu nhiên
            $typeName = array_rand($types);       // key = type name
            $subjectName = array_rand($subjects); // key = subject name

            $documents[] = [
                'title' => "$typeName - $subjectName #$i",
                'description' => "Tài liệu $typeName cho môn $subjectName, bản thứ $i",
                'status' => $statuses[array_rand($statuses)],
                'share_mode' => $shareModes[array_rand($shareModes)],
                'share_link' => rand(0, 9) < 5 ? "https://share.test/$i" : null,
                'no_expiry' => rand(0, 1),
                'expiration_date' => rand(0, 9) < 7 ? now()->addDays(rand(1, 30)) : null,
                'user_id' => $users[array_rand($users)],
                'folder_id' => $folders[array_rand($folders)],
                'type_id' => $types[$typeName],
                'subject_id' => $subjects[$subjectName],
                'created_at' => now()->subDays(rand(0, 60)),
                'updated_at' => now(),
            ];
        }

        // Bulk insert documents theo chunk 500
        foreach (array_chunk($documents, 500) as $chunk) {
            DB::table('documents')->insert($chunk);
        }

        // Lấy ID vừa insert
        $documentIds = DB::table('documents')
            ->orderByDesc('document_id')
            ->limit(self::MAX_RECORD)
            ->pluck('document_id')
            ->toArray();

        // Gán tag ngẫu nhiên cho từng document, tránh trùng
        $documentTagsMap = [];
        foreach ($documentIds as $documentId) {
            $randomTags = collect($tags)->shuffle()->take(rand(self::TAGS_PER_DOC_MIN, self::TAGS_PER_DOC_MAX))->toArray();
            foreach ($randomTags as $tagId) {
                $key = $documentId . '-' . $tagId;
                $documentTagsMap[$key] = [
                    'document_id' => $documentId,
                    'tag_id' => $tagId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Bulk insert document_tags
        foreach (array_chunk(array_values($documentTagsMap), 500) as $chunk) {
            DB::table('document_tags')->insert($chunk);
        }
    }
}
