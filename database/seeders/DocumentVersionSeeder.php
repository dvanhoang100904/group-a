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

    const MAX_RECORD = 100;

    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id');
        $users = DB::table('users')->pluck('user_id');

        foreach (range(1, self::MAX_RECORD) as $i) {
            $documentId = $documents->random();
            $userId = $users->random();

            // Lấy version mới nhất
            $lastVersion = DB::table('document_versions')
                ->where('document_id', $documentId)
                ->orderByDesc('version_number')
                ->first();

            $versionNumber = $lastVersion ? $lastVersion->version_number + 1 : 1;
            $filePath = "docs/doc{$documentId}_v{$versionNumber}.pdf";

            // Tạo PDF hợp lệ bằng Dompdf
            $pdf = Pdf::loadHTML("
                <h1>Document {$documentId}</h1>
                <p>Version: {$versionNumber}</p>
                <p>Created by User: {$userId}</p>
            ");
            $pdfContent = $pdf->output();

            Storage::disk('public')->put($filePath, $pdfContent);

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

            if ($versionNumber > 1) {
                DB::table('document_versions')
                    ->where('document_id', $documentId)
                    ->where('version_number', '<', $versionNumber)
                    ->update(['is_current_version' => 0]);
            }
        }
    }
}
