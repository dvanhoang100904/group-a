<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentPreviewSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */

    const MAX_RECORD = 100;

    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();

        $previewCounter = 1;

        foreach (range(1, self::MAX_RECORD) as $_) {
            $documentId = $documents[array_rand($documents)];
            $userId = $users[array_rand($users)];

            $versions = DB::table('document_versions')
                ->where('document_id', $documentId)
                ->pluck('version_id')
                ->toArray();

            if (empty($versions)) continue;

            $versionId = $versions[array_rand($versions)];

            // Tạo từ 1-3 preview cho mỗi version
            $numPreviews = rand(1, 3);

            for ($j = 1; $j <= $numPreviews; $j++) {
                $previewPath = "previews/doc{$documentId}_v{$versionId}_preview{$previewCounter}.png";

                // Tạo placeholder preview nếu muốn
                if (!Storage::disk('public')->exists($previewPath)) {
                    Storage::disk('public')->put($previewPath, '');
                }

                DB::table('document_previews')->insert([
                    'preview_path' => $previewPath,
                    'expires_at' => rand(0, 1) ? now()->addDays(rand(5, 15)) : null,
                    'generated_by' => $userId,
                    'document_id' => $documentId,
                    'version_id' => $versionId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $previewCounter++;
            }
        }
    }
}
