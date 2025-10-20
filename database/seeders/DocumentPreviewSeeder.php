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

    const MAX_PREVIEWS = 50; // tổng preview tạo ra
    const MAX_PREVIEW_PER_VERSION = 3; // số preview tối đa cho mỗi version

    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();

        $previewCounter = 1;

        foreach (range(1, self::MAX_PREVIEWS) as $_) {
            if (empty($documents) || empty($users)) continue;

            $documentId = $documents[array_rand($documents)];
            $userId = $users[array_rand($users)];

            // Lấy version của document
            $versions = DB::table('document_versions')
                ->where('document_id', $documentId)
                ->pluck('version_id')
                ->toArray();

            if (empty($versions)) continue;

            $versionId = $versions[array_rand($versions)];

            // Tạo 1-3 preview
            $numPreviews = rand(1, self::MAX_PREVIEW_PER_VERSION);
            for ($i = 1; $i <= $numPreviews; $i++) {
                $previewPath = "previews/doc{$documentId}_v{$versionId}_preview{$previewCounter}.png";

                if (!Storage::disk('public')->exists($previewPath)) {
                    Storage::disk('public')->put($previewPath, ''); // placeholder
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
