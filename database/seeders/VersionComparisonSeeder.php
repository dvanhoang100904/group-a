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

    const MAX_RECORD = 20; // số bản so sánh

    public function run(): void
    {
        $versions = DB::table('document_versions')->pluck('version_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();
        $diffResults = ['No changes', 'Minor changes', 'Major changes'];

        if (count($versions) < 2) return; // ít nhất 2 version mới so sánh được

        foreach (range(1, self::MAX_RECORD) as $_) {
            // Chọn 2 version khác nhau
            $selectedVersions = collect($versions)->shuffle()->take(2)->values();
            $baseVersion = $selectedVersions[0];
            $compareVersion = $selectedVersions[1];

            DB::table('version_comparisons')->insert([
                'base_version_id' => $baseVersion,
                'compare_version_id' => $compareVersion,
                'diff_result' => $diffResults[array_rand($diffResults)],
                'compared_by' => $users[array_rand($users)],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
