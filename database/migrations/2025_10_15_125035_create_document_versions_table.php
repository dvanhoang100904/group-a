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
        Schema::create('document_versions', function (Blueprint $table) {
            $table->increments('version_id');
            $table->unsignedInteger('version_number');
            $table->string('file_path', 255);
            $table->unsignedInteger('file_size');
            $table->string('mime_type', 100);
            $table->boolean('is_current_version')->default(0);
            $table->string('change_note', 255)->nullable();
            $table->unsignedInteger('document_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->index('user_id', 'idx_user');

            $table->index(['document_id', 'created_at'], 'idx_doc_created');
            $table->index(['document_id', 'version_number'], 'idx_doc_version');
            $table->index(['document_id', 'is_current_version'], 'idx_doc_current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
