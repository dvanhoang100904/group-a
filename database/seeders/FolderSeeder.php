<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class FolderSeeder extends Seeder
{
    private $batchSize = 1000;
    private $currentFolderId = 1;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting Folder Seeder...');

        // Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('folders')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $totalUsers = 100;
        $folders = [];

        for ($userId = 1; $userId <= $totalUsers; $userId++) {
            $folders = array_merge($folders, $this->generateUserFolders($userId));

            // Insert in batches
            if (count($folders) >= $this->batchSize) {
                DB::table('folders')->insert($folders);
                $folders = [];
                $this->command->info("📊 Processed user {$userId}/{$totalUsers}");
            }
        }

        // Insert remaining folders
        if (!empty($folders)) {
            DB::table('folders')->insert($folders);
        }

        $totalFolders = $this->currentFolderId - 1;
        $this->command->info("✅ Folder seeder completed!");
        $this->command->info("📁 Total folders created: {$totalFolders}");
    }

    /**
     * Generate folders for a specific user
     */
    private function generateUserFolders(int $userId): array
    {
        $folders = [];
        $now = Carbon::now();

        // 10 main folders per user
        for ($i = 1; $i <= 10; $i++) {
            $mainFolderId = $this->currentFolderId++;

            // Main folder (level 1)
            $folders[] = [
                'folder_id' => $mainFolderId,
                'name' => $this->generateFolderName($userId, $i, null, null),
                // ĐÃ XÓA STATUS
                'parent_folder_id' => null,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            // 2 child folders for each main folder (level 2)
            for ($j = 1; $j <= 2; $j++) {
                $childFolderId = $this->currentFolderId++;

                $folders[] = [
                    'folder_id' => $childFolderId,
                    'name' => $this->generateFolderName($userId, $i, $j, null),
                    // ĐÃ XÓA STATUS
                    'parent_folder_id' => $mainFolderId,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                // 1 grandchild folder for each child folder (level 3)
                $grandChildFolderId = $this->currentFolderId++;

                $folders[] = [
                    'folder_id' => $grandChildFolderId,
                    'name' => $this->generateFolderName($userId, $i, $j, 1),
                    // ĐÃ XÓA STATUS
                    'parent_folder_id' => $childFolderId,
                    'user_id' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        return $folders;
    }

    /**
     * Generate folder name based on hierarchy
     */
    private function generateFolderName(int $userId, int $mainIndex, ?int $childIndex, ?int $grandChildIndex): string
    {
        if ($grandChildIndex !== null) {
            return "U{$userId}_Main{$mainIndex}_Child{$childIndex}_Sub{$grandChildIndex}";
        }

        if ($childIndex !== null) {
            return "U{$userId}_Main{$mainIndex}_Child{$childIndex}";
        }

        return "U{$userId}_Main{$mainIndex}";
    }
}
