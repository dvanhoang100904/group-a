<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportSeeder extends Seeder
{
    const MAX_RECORD = 100;
    const MAX_REPORT_PER_DOC = 3;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id')->toArray();
        $users = DB::table('users')->pluck('user_id')->toArray();
        $reasons = ['Sai thông tin', 'Lỗi nội dung', 'Không đầy đủ', 'Cần cập nhật'];
        $statuses = ['new', 'resolved'];

        $reportCount = 0;

        shuffle($documents); // random thứ tự document
        foreach ($documents as $documentId) {
            if ($reportCount >= self::MAX_RECORD) {
                break;
            }

            $numReports = rand(1, min(self::MAX_REPORT_PER_DOC, count($users)));

            $assignedUsers = [];

            for ($i = 0; $i < $numReports && $reportCount < self::MAX_RECORD; $i++) {
                // Chọn user khác nhau
                do {
                    $userId = $users[array_rand($users)];
                } while (in_array($userId, $assignedUsers));

                $assignedUsers[] = $userId;

                $status = $statuses[array_rand($statuses)];
                $resolvedAt = $status === 'resolved' ? now()->subDays(rand(0, 10)) : null;

                DB::table('reports')->insert([
                    'reason' => $reasons[array_rand($reasons)],
                    'status' => $status,
                    'document_id' => $documentId,
                    'user_id' => $userId,
                    'resolved_at' => $resolvedAt,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                $reportCount++;
            }
        }
    }
}
