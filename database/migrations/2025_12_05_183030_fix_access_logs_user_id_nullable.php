<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            // 1. Drop foreign key + index cũ
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);

            // 2. Đổi cột thành nullable + đúng kiểu với users.user_id (INT UNSIGNED)
            $table->unsignedInteger('user_id')->nullable()->change();

            // 3. Tạo lại foreign key đúng chuẩn
            $table->foreign('user_id')
                  ->references('user_id')
                  ->on('users')
                  ->nullOnDelete(); // khi user xóa → log vẫn giữ, user_id = null
        });
    }

    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->unsignedInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('user_id')->on('users')->cascadeOnDelete();
        });
    }
};