<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentPreviewSeeder extends Seeder
{
    const MAX_RECORD = 15;
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $documentId = $documents[array_rand($documents)];
            $userId = $users[array_rand($users)];

            $versions = DB::table('document_versions')
                ->where('document_id', $documentId)
                ->pluck('version_id')
                ->toArray();

            if (empty($versions)) continue;

            $versionId = $versions[array_rand($versions)];

            DB::table('document_previews')->insert([
                'preview_path' => "previews/doc{$documentId}_v{$versionId}_preview.png",
                'expires_at' => rand(0, 1) ? now()->addDays(rand(5, 15)) : null,
                'generated_by' => $userId,
                'document_id' => $documentId,
                'version_id' => $versionId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
