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
        Schema::create('activities', function (Blueprint $table) {
            $table->increments('activity_id');
            $table->enum('action', ['view', 'upload', 'download', 'edit', 'delete', 'share', 'restore', 'report']);
            $table->json('action_detail')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->unsignedInteger('document_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('version_id')->nullable();
            $table->unsignedInteger('folder_id')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'user_id'], 'idx_doc_user');

            $table->index('version_id', 'idx_version');
            $table->index('folder_id', 'idx_folder');

            $table->index(['document_id', 'user_id', 'created_at'], 'idx_doc_user_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
