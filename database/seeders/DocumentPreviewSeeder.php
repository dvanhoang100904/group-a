<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        // Tạo thư mục previews nếu chưa có
        Storage::disk('public')->makeDirectory('previews');

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $documentId = $documents[array_rand($documents)];
            $userId = $users[array_rand($users)];

            $versions = DB::table('document_versions')
                ->where('document_id', $documentId)
                ->pluck('version_id')
                ->toArray();

            if (empty($versions)) continue;

            $versionId = $versions[array_rand($versions)];

            $previewFile = "previews/doc{$documentId}_v{$versionId}_preview.png";

            // Tạo file PNG dummy
            $width = 600;
            $height = 800;
            $image = imagecreatetruecolor($width, $height);
            $bg = imagecolorallocate($image, 220, 220, 220); // nền xám nhạt
            imagefill($image, 0, 0, $bg);

            $textColor = imagecolorallocate($image, 0, 0, 0);
            $text = "Preview Doc {$documentId} v{$versionId}";
            imagestring($image, 5, 50, 50, $text, $textColor);

            ob_start();
            imagepng($image);
            $imgData = ob_get_clean();
            imagedestroy($image);

            Storage::disk('public')->put($previewFile, $imgData);

            // Ghi vào database
            DB::table('document_previews')->insert([
                'preview_path' => $previewFile,
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
