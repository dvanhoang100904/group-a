<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id('access_log_id');            // AUTO_INCREMENT BIGINT
            $table->unsignedInteger('user_id')->nullable(); // Match với users.user_id (INT UNSIGNED)
            $table->string('action', 50);           // login, logout, document_view, document_upload...
            $table->unsignedBigInteger('target_id')->nullable(); // document_id, comment_id...
            $table->string('target_type', 50)->nullable();       // 'document', 'user', 'system'
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('url')->nullable();
            $table->text('description')->nullable(); // chi tiết thao tác
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('user_id');
            $table->index('action');
            $table->index(['target_id', 'target_type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
