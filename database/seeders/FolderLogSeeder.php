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

    const MAX_RECORD = 20;

    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();

        $allFolderIds = json_decode(file_get_contents(storage_path('folder_ids.json')), true);

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $documentId = $documents[array_rand($documents)];
            $movedBy = $users[array_rand($users)];

            $fromFolder = $allFolderIds[array_rand($allFolderIds)];

            do {
                $toFolder = $allFolderIds[array_rand($allFolderIds)];
            } while ($toFolder == $fromFolder);

            DB::table('folder_logs')->insert([
                'document_id' => $documentId,
                'from_folder_id' => $fromFolder,
                'to_folder_id' => $toFolder,
                'moved_by' => $movedBy,
                'moved_at' => now()->subDays(rand(0, 30)),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
