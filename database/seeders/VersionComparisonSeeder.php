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

    const MAX_RECORD = 10;

    public function run(): void
    {
        $versions = DB::table('document_versions')->pluck('version_id');
        $users = DB::table('users')->pluck('user_id');
        $diffResults = ['No changes', 'Minor changes', 'Major changes'];

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $selectedVersions = $versions->shuffle()->take(2)->values();
            $baseVersion = $selectedVersions[0];
            $compareVersion = $selectedVersions[1];

            DB::table('version_comparisons')->insert([
                'diff_result' => $diffResults[array_rand($diffResults)],
                'base_version_id' => $baseVersion,
                'compare_version_id' => $compareVersion,
                'compared_by' => $users->random(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
