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
        Schema::table('folder_logs', function (Blueprint $table) {
            $table->foreign('document_id')->references('document_id')->on('documents')->onDelete('cascade');
            $table->foreign('from_folder_id')->references('folder_id')->on('folders')->nullOnDelete();
            $table->foreign('to_folder_id')->references('folder_id')->on('folders')->nullOnDelete();
            $table->foreign('moved_by')->references('user_id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folder_logs', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->dropForeign(['from_folder_id']);
            $table->dropForeign(['to_folder_id']);
            $table->dropForeign(['moved_by']);
        });
    }
};
