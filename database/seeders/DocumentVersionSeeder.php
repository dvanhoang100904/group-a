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

    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();

        if (empty($documents) || empty($users)) return;

        foreach ($documents as $documentId) {
            // Tạo 1–5 version cho mỗi document
            $numVersions = rand(1, 5);

            for ($v = 1; $v <= $numVersions; $v++) {
                $userId = $users[array_rand($users)];

                // Tạo file PDF placeholder
                $filePath = "docs/doc{$documentId}_v{$v}.pdf";
                $pdfContent = Pdf::loadHTML("<h1>Document {$documentId}</h1><p>Version: {$v}</p>")->output();
                Storage::disk('public')->put($filePath, $pdfContent);

                // Insert version
                DB::table('document_versions')->insert([
                    'version_number' => $v,
                    'file_path' => $filePath,
                    'file_size' => strlen($pdfContent),
                    'mime_type' => 'application/pdf',
                    'is_current_version' => 1,
                    'change_note' => $v === 1 ? 'Initial version' : 'Updated version',
                    'document_id' => $documentId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Nếu có version trước, đánh dấu version cũ không còn là current
                if ($v > 1) {
                    DB::table('document_versions')
                        ->where('document_id', $documentId)
                        ->where('version_number', '<', $v)
                        ->update(['is_current_version' => 0]);
                }
            }
        }
    }
}
