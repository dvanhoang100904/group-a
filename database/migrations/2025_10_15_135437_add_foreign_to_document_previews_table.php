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
        Schema::table('document_previews', function (Blueprint $table) {
            $table->foreign('document_id')->references('document_id')->on('documents')->onDelete('cascade');
            $table->foreign('version_id')->references('version_id')->on('document_versions')->nullOnDelete();
            $table->foreign('generated_by')->references('user_id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_previews', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->dropForeign(['version_id']);
            $table->dropForeign(['generated_by']);
        });
    }
};
