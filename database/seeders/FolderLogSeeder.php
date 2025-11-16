<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FolderLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    const MAX_RECORD = 500; // Có thể tăng nếu muốn
    const CHUNK_SIZE = 200; // Số bản ghi insert/lần

    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();
        $allFolderIds = json_decode(file_get_contents(storage_path('folder_ids.json')), true);

        if (empty($documents) || empty($users) || empty($allFolderIds)) {
            return;
        }

        $logs = [];

        for ($i = 0; $i < self::MAX_RECORD; $i++) {
            $documentId = $documents[array_rand($documents)];
            $movedBy = $users[array_rand($users)];

            $fromFolder = $allFolderIds[array_rand($allFolderIds)];
            do {
                $toFolder = $allFolderIds[array_rand($allFolderIds)];
            } while ($toFolder == $fromFolder);

            $logs[] = [
                'document_id' => $documentId,
                'from_folder_id' => $fromFolder,
                'to_folder_id' => $toFolder,
                'moved_by' => $movedBy,
                'moved_at' => now()->subDays(rand(0, 30)),
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        // Bulk insert theo chunk
        foreach (array_chunk($logs, self::CHUNK_SIZE) as $chunk) {
            DB::table('folder_logs')->insert($chunk);
        }
    }
}
