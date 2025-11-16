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

    public function run(): void
    {
        $versions = DB::table('document_versions')->get();
        if ($versions->isEmpty()) return;

        $previews = [];

        foreach ($versions as $version) {
            $originalFilePath = storage_path("app/public/{$version->file_path}");
            $previewFilePath = "documents/{$version->document_id}/preview_{$version->version_number}.pdf";

            // Nếu file gốc tồn tại, tạo preview (copy)
            if (Storage::disk('public')->exists($version->file_path)) {
                Storage::disk('public')->put($previewFilePath, Storage::disk('public')->get($version->file_path));
            }

            $previews[] = [
                'preview_path' => $previewFilePath,
                'expires_at' => now()->addDays(rand(7, 30)), // random để realistic hơn
                'generated_by' => $version->user_id,
                'document_id' => $version->document_id,
                'version_id' => $version->version_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($previews, 500) as $chunk) {
            DB::table('document_previews')->insert($chunk);
        }
    }
}
