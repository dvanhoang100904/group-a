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

    const MAX_RECORD = 1000;

    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();
        $roles = DB::table('roles')->pluck('role_id')->toArray();

        if (empty($documents) || empty($users) || empty($roles)) {
            return; // Tránh lỗi nếu table rỗng
        }

        $accesses = [];

        $grantedToTypes = ['user', 'role', 'link'];

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $documentId = $documents[array_rand($documents)];
            $grantedBy = $users[array_rand($users)];

            $grantedToType = $grantedToTypes[array_rand($grantedToTypes)];

            $grantedToUser = null;
            $grantedToRole = null;
            $shareLink = null;

            if ($grantedToType === 'user') {
                // Tránh cấp quyền cho chính mình
                do {
                    $grantedToUser = $users[array_rand($users)];
                } while ($grantedToUser === $grantedBy);
            } elseif ($grantedToType === 'role') {
                $grantedToRole = $roles[array_rand($roles)];
            } elseif ($grantedToType === 'link') {
                $shareLink = "https://share.test/$i";
            }

            $noExpiry = rand(0, 1);
            $expirationDate = $noExpiry ? null : (rand(0, 9) < 7 ? now()->addDays(rand(1, 30)) : null);

            $accesses[] = [
                'share_link' => $shareLink,
                'granted_to_type' => $grantedToType,
                'can_view' => rand(0, 1),
                'can_edit' => rand(0, 1),
                'can_delete' => rand(0, 1),
                'can_upload' => rand(0, 1),
                'can_download' => rand(0, 1),
                'can_share' => rand(0, 1),
                'no_expiry' => $noExpiry,
                'expiration_date' => $expirationDate,
                'document_id' => $documentId,
                'granted_by' => $grantedBy,
                'granted_to_user_id' => $grantedToUser,
                'granted_to_role_id' => $grantedToRole,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert theo chunk 500 record/lần
        foreach (array_chunk($accesses, 500) as $chunk) {
            DB::table('document_accesses')->insert($chunk);
        }
    }
}
