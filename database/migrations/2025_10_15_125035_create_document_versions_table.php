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
    $table->id('version_id');
    $table->unsignedInteger('version_number');
    $table->string('file_path');
    $table->unsignedInteger('file_size');
    $table->string('mime_type', 100);
    $table->boolean('is_current_version')->default(false);
    $table->string('change_note')->nullable();

    $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
    $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

    $table->timestamps();
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
