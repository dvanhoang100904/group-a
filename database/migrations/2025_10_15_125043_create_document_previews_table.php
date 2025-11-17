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
        Schema::create('document_previews', function (Blueprint $table) {
            $table->increments('preview_id');
            $table->string('preview_path', 255);
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('generated_by')->nullable();
            $table->unsignedInteger('document_id');
            $table->unsignedInteger('version_id')->nullable();
            $table->timestamps();

            $table->index('generated_by', 'idx_preview_generated_by');
            $table->index(['document_id', 'version_id', 'expires_at'], 'idx_doc_ver_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_previews');
    }
};
