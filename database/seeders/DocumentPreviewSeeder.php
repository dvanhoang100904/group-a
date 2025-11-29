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

        // Tạo folder mẫu nếu chưa tồn tại
        if (!Storage::disk('public')->exists('documents')) {
            Storage::disk('public')->makeDirectory('documents');
        }

        foreach ($versions as $version) {
            $previewFilePath = "documents/{$version->document_id}/preview_{$version->version_number}.pdf";

            // Copy file mẫu nhỏ thay vì PDF gốc
            if (!Storage::disk('public')->exists($previewFilePath)) {
                $sampleFile = storage_path('app/public/sample_preview.pdf'); // tạo sẵn file nhỏ
                Storage::disk('public')->put($previewFilePath, file_exists($sampleFile) ? file_get_contents($sampleFile) : '');
            }

            $previews[] = [
                'preview_path' => $previewFilePath,
                'expires_at' => now()->addDays(rand(7, 30)),
                'generated_by' => 1, // admin/system
                'document_id' => $version->document_id,
                'version_id' => $version->version_id,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert theo chunk
        foreach (array_chunk($previews, 500) as $chunk) {
            DB::table('document_previews')->insert($chunk);
        }
    }
}
