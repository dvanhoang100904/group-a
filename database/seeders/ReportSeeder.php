<?php

namespace Database\Seeders;

use Carbon\Carbon;
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
        $statuses = ['new', 'processing', 'resolved'];

        $reportCount = 0;
        $reports = [];

        shuffle($documents); // random thứ tự document
        foreach ($documents as $documentId) {
            if ($reportCount >= self::MAX_RECORD) break;

            $numReports = rand(1, min(self::MAX_REPORT_PER_DOC, count($users)));
            $assignedUsers = [];

            for ($i = 0; $i < $numReports && $reportCount < self::MAX_RECORD; $i++) {
                // Chọn user khác nhau cho document
                do {
                    $userId = $users[array_rand($users)];
                } while (in_array($userId, $assignedUsers));

                $assignedUsers[] = $userId;

                $status = $statuses[array_rand($statuses)];

                // Random created_at trong 60 ngày gần đây
                $createdAt = Carbon::now()->subDays(rand(0, 60))->subMinutes(rand(0, 1440));
                $resolvedAt = null;
                if ($status === 'resolved') {
                    // resolved_at phải sau created_at
                    $resolvedAt = (clone $createdAt)->addDays(rand(0, 10))->addMinutes(rand(0, 1440));
                }

                // Kiểm tra trùng document + user trước khi insert
                if (!DB::table('reports')->where([
                    'document_id' => $documentId,
                    'user_id' => $userId
                ])->exists()) {
                    $reports[] = [
                        'reason' => $reasons[array_rand($reasons)],
                        'status' => $status,
                        'document_id' => $documentId,
                        'user_id' => $userId,
                        'resolved_at' => $resolvedAt,
                        'created_at' => $createdAt,
                        'updated_at' => Carbon::now()
                    ];

                    $reportCount++;
                }
            }
        }

        // Bulk insert theo chunk
        foreach (array_chunk($reports, 50) as $chunk) {
            DB::table('reports')->insert($chunk);
        }
    }
}
