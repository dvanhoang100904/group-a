<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'code')) {
                $table->string('code', 20)->unique()->after('subject_id')->comment('Mã môn học');
            }

            if (!Schema::hasColumn('subjects', 'credits')) {
                $table->unsignedTinyInteger('credits')->default(3)->after('name')->comment('Số tín chỉ');
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (Schema::hasColumn('subjects', 'credits')) {
                $table->dropColumn('credits');
            }
            if (Schema::hasColumn('subjects', 'code')) {
                $table->dropColumn('code');
            }
        });
    }
};
