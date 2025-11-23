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
        $users = DB::table('users')->pluck('user_id')->toArray();
        $folders = DB::table('folders')->get()->groupBy('user_id')->toArray();
        $types = DB::table('types')->pluck('type_id', 'name')->toArray();
        $subjects = DB::table('subjects')->pluck('subject_id', 'name')->toArray();
        $tags = DB::table('tags')->pluck('tag_id')->toArray();

        if (empty($users) || empty($folders) || empty($types) || empty($subjects) || empty($tags)) {
            return;
        }

        $statuses = ['private', 'public', 'restricted'];
        $shareModes = ['private', 'public', 'custom'];

        $documents = [];
        $documentCounter = 1;

        foreach ($users as $userId) {
            $userFolders = $folders[$userId] ?? [];

            // 1. 10 files với folder_id = null (level 1)
            for ($i = 1; $i <= 10; $i++) {
                $documents[] = $this->createDocument(
                    $documentCounter,
                    $userId,
                    null, // folder_id = null
                    $types,
                    $subjects,
                    $statuses,
                    $shareModes
                );
                $documentCounter++;
            }

            // 2. Lấy 10 folders level 1 (parent_folder_id = null)
            $level1Folders = array_filter($userFolders, function ($folder) {
                return $folder->parent_folder_id === null;
            });

            // Chỉ lấy 10 folders level 1 đầu tiên
            $level1Folders = array_slice($level1Folders, 0, 10);

            foreach ($level1Folders as $level1Folder) {
                // 2a. 1 file trong mỗi folder level 1 (level 2)
                $documents[] = $this->createDocument(
                    $documentCounter,
                    $userId,
                    $level1Folder->folder_id, // trong folder level 1
                    $types,
                    $subjects,
                    $statuses,
                    $shareModes
                );
                $documentCounter++;

                // 2b. Tìm folder level 2 con của folder level 1 này
                $level2Folders = array_filter($userFolders, function ($folder) use ($level1Folder) {
                    return $folder->parent_folder_id === $level1Folder->folder_id;
                });

                // Lấy folder level 2 đầu tiên
                $level2Folder = reset($level2Folders);

                if ($level2Folder) {
                    // 2c. 1 file trong folder level 2 (level 3)
                    $documents[] = $this->createDocument(
                        $documentCounter,
                        $userId,
                        $level2Folder->folder_id, // trong folder level 2
                        $types,
                        $subjects,
                        $statuses,
                        $shareModes
                    );
                    $documentCounter++;
                }
            }
        }

        // Bulk insert
        foreach (array_chunk($documents, 500) as $chunk) {
            DB::table('documents')->insert($chunk);
        }

        // Gán tags
        $this->assignTags($documentCounter - 1, $tags);
    }

    private function createDocument($counter, $userId, $folderId, $types, $subjects, $statuses, $shareModes)
    {
        $typeName = array_rand($types);
        $subjectName = array_rand($subjects);

        return [
            'title' => "$typeName - $subjectName #$counter",
            'description' => "Tài liệu $typeName cho môn $subjectName, bản thứ $counter",
            'status' => $statuses[array_rand($statuses)],
            'share_mode' => $shareModes[array_rand($shareModes)],
            'share_link' => rand(0, 9) < 5 ? "https://share.test/$counter" : null,
            'no_expiry' => rand(0, 1),
            'expiration_date' => rand(0, 9) < 7 ? now()->addDays(rand(1, 30)) : null,
            'user_id' => $userId,
            'folder_id' => $folderId,
            'type_id' => $types[$typeName],
            'subject_id' => $subjects[$subjectName],
            'created_at' => now()->subDays(rand(0, 60)),
            'updated_at' => now(),
        ];
    }

    private function assignTags($totalDocs, $tags)
    {
        $docIds = DB::table('documents')
            ->orderByDesc('document_id')
            ->limit($totalDocs)
            ->pluck('document_id')
            ->toArray();

        $docTags = [];
        foreach ($docIds as $docId) {
            $randomTags = collect($tags)->shuffle()->take(rand(self::TAGS_PER_DOC_MIN, self::TAGS_PER_DOC_MAX))->toArray();
            foreach ($randomTags as $tagId) {
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
