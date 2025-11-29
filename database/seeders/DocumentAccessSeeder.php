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
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id', 'role_id')->toArray();
        $roles = DB::table('roles')->pluck('role_id')->toArray();

        if (empty($documents) || empty($users) || empty($roles)) {
            return;
        }

        // Chỉ admin/giảng viên có thể cấp quyền
        $adminsAndLecturers = DB::table('users')
            ->whereIn('role_id', [1, 2]) // giả sử 1=admin, 2=giảng viên
            ->pluck('user_id')
            ->toArray();

        $accesses = [];
        $grantedToTypes = ['user', 'role', 'link'];

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $documentId = $documents[array_rand($documents)];
            $grantedBy = $adminsAndLecturers[array_rand($adminsAndLecturers)];

            $grantedToType = $grantedToTypes[array_rand($grantedToTypes)];

            $grantedToUser = null;
            $grantedToRole = null;
            $shareLink = null;

            if ($grantedToType === 'user') {
                // Chỉ cấp quyền cho sinh viên (role_id = 3)
                $students = DB::table('users')->where('role_id', 3)->pluck('user_id')->toArray();
                if (!empty($students)) {
                    $grantedToUser = $students[array_rand($students)];
                }
            } elseif ($grantedToType === 'role') {
                // Chọn role sinh viên
                $grantedToRole = 3;
            } elseif ($grantedToType === 'link') {
                $shareLink = "https://share.test/$i";
            }

            $noExpiry = rand(0, 1);
            $expirationDate = $noExpiry ? null : now()->addDays(rand(1, 30));

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

        foreach (array_chunk($accesses, 500) as $chunk) {
            DB::table('document_accesses')->insert($chunk);
        }
    }
}
