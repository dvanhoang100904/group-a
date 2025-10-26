<?php

namespace Database\Seeders;

use App\Models\VersionComparison;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // Các seeder cơ bản
        $this->call([
            UserSeeder::class,
            RoleSeeder::class,
            DepartmentSeeder::class,
            SubjectSeeder::class,
            TypeSeeder::class,
            FolderSeeder::class,
            TagSeeder::class,
            DocumentSeeder::class,
            DocumentVersionSeeder::class,
            DocumentPreviewSeeder::class,
            DocumentAccessSeeder::class,
            VersionComparisonSeeder::class,
            ActivitySeeder::class,
            ReportSeeder::class,
            FolderLogSeeder::class,
        ]);
    }
}
