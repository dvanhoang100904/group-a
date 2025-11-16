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

    public function run(): void
    {
        $users = DB::table('users')->pluck('user_id')->toArray();
        $folders = DB::table('folders')->pluck('folder_id')->toArray();
        $types = DB::table('types')->pluck('type_id', 'name')->toArray();
        $subjects = DB::table('subjects')->pluck('subject_id', 'name')->toArray();
        $tagIds = DB::table('tags')->pluck('tag_id')->toArray();

        $statuses = ['private', 'public', 'restricted'];
        $shareModes = ['private', 'public', 'custom'];

        if (empty($users) || empty($folders) || empty($types) || empty($subjects)) {
            return; // Tránh lỗi nếu table rỗng
        }

        $documents = [];
        $documentTags = [];

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $typeName = array_rand($types);
            $subjectName = array_rand($subjects);

            $noExpiry = rand(0, 1);
            $expirationDate = $noExpiry ? null : (rand(0, 9) < 7 ? now()->addDays(rand(1, 30)) : null);
            $shareLink = rand(0, 9) < 5 ? "https://share.test/$i" : null; // 50% có link

            $documents[] = [
                'title' => "$typeName - $subjectName #$i",
                'description' => "Tài liệu $typeName cho môn $subjectName, bản thứ $i",
                'status' => $statuses[array_rand($statuses)],
                'share_mode' => $shareModes[array_rand($shareModes)],
                'share_link' => $shareLink,
                'no_expiry' => $noExpiry,
                'expiration_date' => $expirationDate,
                'user_id' => $users[array_rand($users)],
                'folder_id' => $folders[array_rand($folders)],
                'type_id' => $types[$typeName],
                'subject_id' => $subjects[$subjectName],
                'created_at' => now()->subDays(rand(0, 60)),
                'updated_at' => now(),
            ];
        }

        // Bulk insert documents, chia chunks 500 để tránh lỗi max_allowed_packet
        $chunks = array_chunk($documents, 500);
        foreach ($chunks as $chunk) {
            DB::table('documents')->insert($chunk);
        }

        // Lấy ID vừa insert
        $documentIds = DB::table('documents')
            ->orderByDesc('document_id')
            ->limit(self::MAX_RECORD)
            ->pluck('document_id')
            ->toArray();

        // Gán 1–3 tag ngẫu nhiên cho mỗi document
        if (!empty($tagIds)) {
            foreach ($documentIds as $documentId) {
                $randomTags = collect($tagIds)->shuffle()->take(rand(1, 3))->toArray();
                foreach ($randomTags as $tagId) {
                    $documentTags[] = [
                        'document_id' => $documentId,
                        'tag_id' => $tagId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Bulk insert tags
            $tagChunks = array_chunk($documentTags, 500);
            foreach ($tagChunks as $chunk) {
                DB::table('document_tags')->insert($chunk);
            }
        }
    }
}
