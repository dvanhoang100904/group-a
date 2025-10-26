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

        foreach ($versions as $version) {
            // Tạo đường dẫn preview cố định
            $previewFilePath = "documents/{$version->document_id}/preview_{$version->version_number}.pdf";

            // Copy file PDF gốc sang path preview
            Storage::disk('public')->put(
                $previewFilePath,
                file_get_contents(storage_path("app/public/{$version->file_path}"))
            );

            // Lưu thông tin preview vào database
            DB::table('document_previews')->insert([
                'preview_path' => $previewFilePath,
                'expires_at' => now()->addDays(30),
                'generated_by' => $version->user_id,
                'document_id' => $version->document_id,
                'version_id' => $version->version_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
