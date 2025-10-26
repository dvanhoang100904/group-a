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
        Schema::table('version_comparisons', function (Blueprint $table) {
            $table->foreign('base_version_id')->references('version_id')->on('document_versions')->onDelete('cascade');
            $table->foreign('compare_version_id')->references('version_id')->on('document_versions')->onDelete('cascade');
            $table->foreign('compared_by')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('version_comparisons', function (Blueprint $table) {
            $table->dropForeign(['base_version_id']);
            $table->dropForeign(['compare_version_id']);
            $table->dropForeign(['compared_by']);
        });
    }
};
