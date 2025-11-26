<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Xóa foreign key cũ nếu tồn tại
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['parent_folder_id']);
            $table->dropForeign(['user_id']);
        });

        // Thêm lại foreign key với cascade
        Schema::table('folders', function (Blueprint $table) {
            $table->foreign('parent_folder_id')
                ->references('folder_id')
                ->on('folders')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['parent_folder_id']);
            $table->dropForeign(['user_id']);
        });
    }
};
