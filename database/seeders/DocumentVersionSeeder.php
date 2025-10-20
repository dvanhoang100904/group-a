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
        $users = DB::table('users')->pluck('user_id');
        $folders = DB::table('folders')->pluck('folder_id');
        $types = DB::table('types')->pluck('type_id', 'name')->toArray();
        $subjects = DB::table('subjects')->pluck('subject_id', 'name')->toArray();
        $statuses = ['private', 'public', 'restricted'];
        $tagIds = DB::table('tags')->pluck('tag_id')->toArray(); // Lấy danh sách tag
        $documents = DB::table('documents')->pluck('document_id')->toArray();

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $documentId = $documents[array_rand($documents)]; // Lấy document có sẵn
            $userId = $users->random();

            // Lấy version mới tiếp theo
            $versionNumber = DB::table('document_versions')
                ->where('document_id', $documentId)
                ->max('version_number') + 1;

            $filePath = "docs/doc{$documentId}_v{$versionNumber}.pdf";
            $pdf = Pdf::loadHTML("<h1>Document {$documentId}</h1><p>Version: {$versionNumber}</p>");
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
