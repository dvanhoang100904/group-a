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

    const MAX_VERSION_PER_DOCUMENT = 5; // Giảm để seed nhanh

    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();

        if (empty($documents) || empty($users)) return;

        $versions = [];

        foreach ($documents as $documentId) {
            $numVersions = rand(1, self::MAX_VERSION_PER_DOCUMENT);

            for ($v = 1; $v <= $numVersions; $v++) {
                $userId = $users[array_rand($users)];

                $filePath = "documents/$documentId/version_$v.pdf";

                $versions[] = [
                    'version_number' => $v,
                    'file_path' => $filePath,
                    'file_size' => 1024, // giả lập
                    'mime_type' => 'application/pdf',
                    'is_current_version' => $v === $numVersions ? 1 : 0,
                    'change_note' => $v === 1 ? 'Initial version' : 'Updated version',
                    'document_id' => $documentId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Bulk insert
        foreach (array_chunk($versions, 500) as $chunk) {
            DB::table('document_versions')->insert($chunk);
        }
    }
}
