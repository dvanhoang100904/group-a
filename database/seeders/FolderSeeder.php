<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    const MAX_RECORD = 100;

    public function run(): void
    {
        $users = DB::table('users')->pluck('user_id')->toArray();

        $allFolderIds = [];

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $folderId = DB::table('folders')->insertGetId([
                'name' => "Folder $i",
                'status' => ['private', 'public'][rand(0, 1)],
                'parent_folder_id' => null,
                'user_id' => $users[array_rand($users)],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $allFolderIds[] = $folderId;

            $subFolderCount = rand(1, 2);
            for ($j = 1; $j <= $subFolderCount; $j++) {
                $subFolderId = DB::table('folders')->insertGetId([
                    'name' => "Folder $i.$j",
                    'status' => ['private', 'public'][rand(0, 1)],
                    'parent_folder_id' => $folderId,
                    'user_id' => $users[array_rand($users)],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $allFolderIds[] = $subFolderId;
            }
        }

        file_put_contents(storage_path('folder_ids.json'), json_encode($allFolderIds));
    }
}
