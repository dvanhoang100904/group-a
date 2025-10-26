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
        Schema::create('documents', function (Blueprint $table) {
            $table->increments('document_id');
            $table->string('title', 150);
            $table->string('description', 255)->nullable();
            $table->enum('status', ['public', 'private', 'restricted'])->default('private');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('folder_id')->nullable();
            $table->unsignedInteger('type_id');
            $table->unsignedInteger('subject_id');
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at'], 'idx_user_status_created');
            $table->index(['subject_id', 'type_id'], 'idx_subject_type');
            $table->index(['folder_id', 'created_at'], 'idx_folder_created');

            $table->index('status', 'idx_status');
            $table->fullText(['title', 'description'], 'ft_title_description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
