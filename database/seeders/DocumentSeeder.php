<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    const MAX_RECORD = 50;

    public function run(): void
    {
        $users = DB::table('users')->pluck('user_id');
        $folders = DB::table('folders')->pluck('folder_id');
        $types = DB::table('types')->pluck('type_id', 'name')->toArray();;
        $subjects = DB::table('subjects')->pluck('subject_id', 'name')->toArray();;
        $statuses = ['private', 'public', 'restricted'];

        for ($i = 1; $i <= self::MAX_RECORD; $i++) {
            $typeName = array_rand($types);
            $subjectName = array_rand($subjects);

            DB::table('documents')->insert([
                'title' => "$typeName - $subjectName #$i",
                'description' => "Tài liệu $typeName cho môn $subjectName, bản thứ $i",
                'status' => $statuses[array_rand($statuses)],
                'user_id' => $users->random(),
                'folder_id' => $folders->random(),
                'type_id' => $types[$typeName],
                'subject_id' => $subjects[$subjectName],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
