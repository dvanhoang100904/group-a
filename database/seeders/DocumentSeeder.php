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

    const MAX_RECORD = 100;

    public function run(): void
    {
        $users = DB::table('users')->pluck('user_id');
        $folders = DB::table('folders')->pluck('folder_id');
        $types = DB::table('types')->pluck('type_id', 'name')->toArray();
        $subjects = DB::table('subjects')->pluck('subject_id', 'name')->toArray();
        $statuses = ['private', 'public', 'restricted'];
        $tagIds = DB::table('tags')->pluck('tag_id')->toArray();

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $typeName = array_rand($types);
            $subjectName = array_rand($subjects);

            $documentId = DB::table('documents')->insertGetId([
                'title' => "$typeName - $subjectName #$i",
                'description' => "Tài liệu $typeName cho môn $subjectName, bản thứ $i",
                'status' => $statuses[array_rand($statuses)],
                'user_id' => $users->random(),
                'folder_id' => $folders->random(),
                'type_id' => $types[$typeName],
                'subject_id' => $subjects[$subjectName],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Gán 1-3 tag ngẫu nhiên, dùng updateOrInsert tránh duplicate
            if (!empty($tagIds)) {
                $randomTags = collect($tagIds)->shuffle()->take(rand(1, 3))->toArray();
                foreach ($randomTags as $tagId) {
                    DB::table('document_tags')->updateOrInsert(
                        ['document_id' => $documentId, 'tag_id' => $tagId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                }
            }
        }
    }
}
