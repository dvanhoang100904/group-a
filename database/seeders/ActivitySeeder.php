<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    const MAX_RECORD = 50;

    public function run(): void
    {
        $actions = ['view', 'upload', 'download', 'edit', 'delete', 'share', 'restore', 'report'];
        $userAgents = ['Chrome', 'Firefox', 'Safari', 'Edge'];

        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();
        $versions = DB::table('document_versions')->pluck('version_id')->toArray();
        $folders = DB::table('folders')->pluck('folder_id')->toArray();

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            DB::table('activities')->insert([
                'action' => $actions[array_rand($actions)],
                'action_detail' => json_encode(['page' => rand(1, 10)]),
                'ip_address' => '127.0.0.' . rand(1, 50),
                'user_agent' => $userAgents[array_rand($userAgents)],
                'document_id' => $documents[array_rand($documents)],
                'user_id' => $users[array_rand($users)],
                'version_id' => $versions[array_rand($versions)],
                'folder_id' => $folders[array_rand($folders)],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
