<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VersionComparisonSeeder extends Seeder
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
            // Lấy tất cả version của document, sắp xếp theo version_number
            $versions = DB::table('document_versions')
                ->where('document_id', $documentId)
                ->orderBy('version_number')
                ->pluck('version_id')
                ->toArray();

            if (count($versions) < 2) continue; // Chỉ tạo comparison nếu có ≥2 version

            for ($i = 0; $i < count($versions) - 1; $i++) {
                DB::table('version_comparisons')->insert([
                    'diff_result' => json_encode([
                        'changes' => "So sánh version {$versions[$i]} vs version {$versions[$i + 1]}"
                    ]),
                    'base_version_id' => $versions[$i],
                    'compare_version_id' => $versions[$i + 1],
                    'compared_by' => $users[array_rand($users)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
