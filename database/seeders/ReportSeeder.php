<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportSeeder extends Seeder
{
    const MAX_RECORD = 100;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $documents = DB::table('documents')->pluck('document_id');
        $users = DB::table('users')->pluck('user_id');
        $reasons = ['Sai thông tin', 'Lỗi nội dung', 'Không đầy đủ', 'Cần cập nhật'];
        $statuses = ['new', 'resolved'];

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $status = $statuses[array_rand($statuses)];
            $resolvedAt = $status === 'resolved' ? now()->subDays(rand(0, 10)) : null;

            DB::table('reports')->insert([
                'reason' => $reasons[array_rand($reasons)],
                'status' => $status,
                'document_id' => $documents->random(),
                'user_id' => $users->random(),
                'resolved_at' => $resolvedAt,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
