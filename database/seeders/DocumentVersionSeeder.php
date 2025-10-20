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

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $typeName = array_rand($types);
            $subjectName = array_rand($subjects);

            // Tạo document
            $documentId = DB::table('documents')->insertGetId([
                'title' => "$typeName - $subjectName #$i",
                'description' => "Tài liệu $typeName cho môn $subjectName, bản thứ $i",
                'status' => $statuses[array_rand($statuses)],
                'user_id' => $users->random(),
                'folder_id' => $folders->random(),
                'type_id' => $types[$typeName],
                'subject_id' => $subjects[$subjectName],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Gán 1–3 tag ngẫu nhiên cho document
            if (!empty($tagIds)) {
                $randomTags = collect($tagIds)->shuffle()->take(rand(1, 3))->toArray();
                foreach ($randomTags as $tagId) {
                    if (!DB::table('document_tags')->where([
                        'document_id' => $documentId,
                        'tag_id' => $tagId
                    ])->exists()) {
                        DB::table('document_tags')->insert([
                            'document_id' => $documentId,
                            'tag_id' => $tagId,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }
                }
            }

            // Tạo 1–5 version PDF cho document
            $numVersions = rand(1, 5);
            for ($v = 1; $v <= $numVersions; $v++) {
                $userId = $users->random();
                $filePath = "docs/doc{$documentId}_v{$v}.pdf";

                $pdf = Pdf::loadHTML("
                    <h1>Document {$documentId}</h1>
                    <p>Version: {$v}</p>
                    <p>Created by User: {$userId}</p>
                ");
                $pdfContent = $pdf->output();
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

                // Đánh dấu version cũ không còn là current
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
