<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    const MAX_RECORD = 50;
    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();
        $roles = DB::table('roles')->pluck('role_id')->toArray();

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $documentId = $documents[array_rand($documents)];
            $grantedBy = $users[array_rand($users)];

            do {
                $grantedToUser = $users[array_rand($users)];
            } while ($grantedToUser == $grantedBy);

            $roleId = $roles[array_rand($roles)];

            DB::table('document_accesses')->insert([
                'share_link' => null,
                'granted_to_type' => 'user',
                'can_view' => rand(0, 1),
                'can_edit' => rand(0, 1),
                'can_delete' => rand(0, 1),
                'can_upload' => rand(0, 1),
                'can_download' => rand(0, 1),
                'can_share' => rand(0, 1),
                'no_expiry' =>  rand(0, 1),
                'expiration_date' => rand(0, 9) < 7 ? now()->addDays(rand(1, 30)) : null,
                'document_id' => $documentId,
                'granted_by' => $grantedBy,
                'granted_to_user_id' => $grantedToUser,
                'granted_to_role_id' => $roleId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
