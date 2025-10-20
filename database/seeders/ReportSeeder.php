<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Report;

class ReportSeeder extends Seeder
{
    const MAX_RECORD = 100;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Tạo 10 báo cáo mẫu
        for ($i = 1; $i <= 20; $i++) {
            Report::create([
                'reason' => 'Báo cáo thử nghiệm #' . $i,
                'status' => 'new',
                'document_id' => rand(1, 5),
                'user_id' => rand(1, 10),
            ]);
        }
    }
}
