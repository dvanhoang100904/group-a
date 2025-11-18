<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->foreign('user_id')
                  ->references('user_id') // KHỚP với cột trong users
                  ->on('users')
                  ->onDelete('set null'); // nếu user bị xóa, log vẫn giữ
        });
    }

    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
};
