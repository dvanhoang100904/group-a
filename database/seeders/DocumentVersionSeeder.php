<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    const MAX_RECORD = 20;

    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();

        foreach (range(1, self::MAX_RECORD) as $_) {
            if (empty($documents) || empty($users)) continue;

            // Chọn ngẫu nhiên document và user
            $documentId = $documents[array_rand($documents)];
            $userId = $users[array_rand($users)];

            // Xác định version tiếp theo
            $versionNumber = (int) DB::table('document_versions')
                ->where('document_id', $documentId)
                ->max('version_number') + 1;

            // Tạo file PDF placeholder
            $filePath = "docs/doc{$documentId}_v{$versionNumber}.pdf";
            $pdfContent = Pdf::loadHTML("<h1>Document {$documentId}</h1><p>Version: {$versionNumber}</p>")->output();
            Storage::disk('public')->put($filePath, $pdfContent);

            // Lưu version vào DB
            DB::table('document_versions')->insert([
                'version_number' => $versionNumber,
                'file_path' => $filePath,
                'file_size' => strlen($pdfContent),
                'mime_type' => 'application/pdf',
                'is_current_version' => 1,
                'change_note' => $versionNumber === 1 ? 'Initial version' : 'Updated version',
                'document_id' => $documentId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Đánh dấu version cũ không còn là current
            if ($versionNumber > 1) {
                DB::table('document_versions')
                    ->where('document_id', $documentId)
                    ->where('version_number', '<', $versionNumber)
                    ->update(['is_current_version' => 0]);
            }
        }
    }
}
