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

    const MAX_RECORD = 100;

    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id');
        $users = DB::table('users')->pluck('user_id');

        // Tạo thư mục nếu chưa có
        Storage::disk('public')->makeDirectory('docs');
        Storage::disk('public')->makeDirectory('previews');

        foreach (range(1, self::MAX_RECORD) as $i) {
            $documentId = $documents->random();
            $userId = $users->random();

            $lastVersion = DB::table('document_versions')
                ->where('document_id', $documentId)
                ->orderByDesc('version_number')
                ->first();

            $versionNumber = $lastVersion ? $lastVersion->version_number + 1 : 1;

            // Chọn ngẫu nhiên PDF hoặc DOCX
            $isPdf = rand(0, 1) === 1;

            if ($isPdf) {
                $filePath = "docs/doc{$documentId}_v{$versionNumber}.pdf";

                $pdf = Pdf::loadHTML("
                    <h1>Document {$documentId}</h1>
                    <p>Version: {$versionNumber}</p>
                    <p>Created by User: {$userId}</p>
                ");
                $pdfContent = $pdf->output();

                Storage::disk('public')->put($filePath, $pdfContent);
                $mimeType = 'application/pdf';
                $fileSize = strlen($pdfContent);
            } else {
                $filePath = "docs/doc{$documentId}_v{$versionNumber}.docx";

                $phpWord = new PhpWord();
                $section = $phpWord->addSection();
                $section->addTitle("Document {$documentId}", 1);
                $section->addText("Version: {$versionNumber}");
                $section->addText("Created by User: {$userId}");

                $tempFile = tempnam(sys_get_temp_dir(), 'docx');
                $writer = IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save($tempFile);

                $content = file_get_contents($tempFile);
                Storage::disk('public')->put($filePath, $content);

                unlink($tempFile);

                $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
                $fileSize = strlen($content);
            }

            // Insert version
            DB::table('document_versions')->insert([
                'version_number' => $versionNumber,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'is_current_version' => 1,
                'change_note' => $versionNumber === 1 ? 'Initial version' : 'Updated version',
                'document_id' => $documentId,
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update các version cũ
            if ($versionNumber > 1) {
                DB::table('document_versions')
                    ->where('document_id', $documentId)
                    ->where('version_number', '<', $versionNumber)
                    ->update(['is_current_version' => 0]);
            }
        }
    }
}
