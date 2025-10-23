<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Barryvdh\DomPDF\Facade\Pdf;

class DocumentVersionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    const MAX_VERSION_PER_DOCUMENT = 5; // Số version tối đa mỗi document

    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();

        if (empty($documents) || empty($users)) return;

        foreach ($documents as $documentId) {
            $numVersions = rand(1, self::MAX_VERSION_PER_DOCUMENT);

            for ($v = 1; $v <= $numVersions; $v++) {
                $userId = $users[array_rand($users)];
                $versionNumber = $v; // dùng số nguyên

                // Tạo file PDF thực tế
                $pdfContent = Pdf::loadHTML("<h1>Document #$documentId</h1><p>Version: v$versionNumber</p>")->output();
                $filePath = "documents/$documentId/version_$v.pdf";
                Storage::disk('public')->put($filePath, $pdfContent);

                // Insert version
                DB::table('document_versions')->insert([
                    'version_number' => $versionNumber, // số nguyên
                    'file_path' => $filePath,
                    'file_size' => Storage::disk('public')->size($filePath),
                    'mime_type' => 'application/pdf',
                    'is_current_version' => $v === $numVersions ? 1 : 0,
                    'change_note' => $v === 1 ? 'Initial version' : 'Updated version',
                    'document_id' => $documentId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
