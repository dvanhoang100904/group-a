<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentSeeder extends Seeder
{
    const TAGS_PER_DOC_MIN = 1;
    const TAGS_PER_DOC_MAX = 3;

    public function run(): void
    {
        $users = DB::table('users')->select('user_id', 'role_id')->get();
        $folders = DB::table('folders')->get()->groupBy('user_id');
        $types = DB::table('types')->pluck('type_id', 'name')->toArray();
        $subjects = DB::table('subjects')->pluck('subject_id', 'name')->toArray();
        $tags = DB::table('tags')->pluck('tag_id')->toArray();

        if ($users->isEmpty() || empty($types) || empty($subjects) || empty($tags)) {
            return;
        }

        $documents = [];
        $documentCounter = 1;

        foreach ($users as $user) {
            $userId = $user->user_id;
            $roleId = $user->role_id;
            $userFolders = $folders[$userId] ?? [];

            $isAdminOrTeacher = in_array($roleId, [1]); // ví dụ: 1=admin/giảng viên

            // 1. 10 files folder_id = null
            for ($i = 1; $i <= 10; $i++) {
                $docData = $this->createDocument($documentCounter, $userId, null, $types, $subjects, $tags, $isAdminOrTeacher);
                $documents[] = $docData;
                $documentCounter++;
            }

            // Folder level 1
            $level1Folders = array_filter($userFolders->all(), fn($f) => $f->parent_folder_id === null);
            $level1Folders = array_slice($level1Folders, 0, 10);

            foreach ($level1Folders as $level1Folder) {
                $docData = $this->createDocument($documentCounter, $userId, $level1Folder->folder_id, $types, $subjects, $tags, $isAdminOrTeacher);
                $documents[] = $docData;
                $documentCounter++;

                // Folder level 2
                $level2Folders = array_filter($userFolders->all(), fn($f) => $f->parent_folder_id === $level1Folder->folder_id);
                $level2Folder = reset($level2Folders);
                if ($level2Folder) {
                    $docData = $this->createDocument($documentCounter, $userId, $level2Folder->folder_id, $types, $subjects, $tags, $isAdminOrTeacher);
                    $documents[] = $docData;
                    $documentCounter++;
                }
            }
        }

        // Bulk insert documents
        foreach (array_chunk($documents, 500) as $chunk) {
            $insertedIds = DB::table('documents')->insertGetId($chunk[0]); // lấy id đầu tiên (mỗi chunk 1 record hoặc xử lý riêng)
            // Nếu cần gán tags từng record, sẽ làm ở bước tiếp theo
        }

        // Gán tags vào pivot table
        $this->assignTags($documents, $tags);
    }

    private function createDocument($counter, $userId, $folderId, $types, $subjects, $tags, $isAdminOrTeacher)
    {
        $typeName = array_rand($types);
        $subjectName = array_rand($subjects);

        $statuses = $isAdminOrTeacher ? ['private', 'public', 'restricted'] : ['private'];
        $shareModes = $isAdminOrTeacher ? ['private', 'public', 'custom'] : ['private'];

        $status = $statuses[array_rand($statuses)];
        $share_mode = $shareModes[array_rand($shareModes)];
        $share_link = in_array($share_mode, ['public', 'custom']) ? "https://share.test/$counter" : null;

        return [
            'title' => "$typeName - $subjectName #$counter",
            'description' => "Tài liệu $typeName cho môn $subjectName, bản thứ $counter",
            'status' => $status,
            'share_mode' => $share_mode,
            'share_link' => $share_link,
            'no_expiry' => rand(0, 1),
            'expiration_date' => rand(0, 9) < 7 ? now()->addDays(rand(1, 30)) : null,
            'user_id' => $userId,
            'folder_id' => $folderId,
            'type_id' => $types[$typeName],
            'subject_id' => $subjects[$subjectName],
            'created_at' => now()->subDays(rand(0, 60)),
            'updated_at' => now(),
            // gán tags ngay khi insert
            'tags' => collect($tags)->shuffle()->take(rand(self::TAGS_PER_DOC_MIN, self::TAGS_PER_DOC_MAX))->toArray(),
        ];
    }

    private function assignTags($documents, $tags)
    {
        $docTags = [];
        foreach ($documents as $docIndex => $doc) {
            $docId = DB::table('documents')->latest('document_id')->skip($docIndex)->first()->document_id;
            foreach ($doc['tags'] as $tagId) {
                $docTags[] = [
                    'document_id' => $docId,
                    'tag_id' => $tagId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        foreach (array_chunk($docTags, 500) as $chunk) {
            DB::table('document_tags')->insert($chunk);
        }
    }
}
