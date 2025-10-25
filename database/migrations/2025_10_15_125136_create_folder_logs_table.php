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
        Schema::create('folder_logs', function (Blueprint $table) {
            $table->increments('log_id');
            $table->unsignedInteger('document_id');
            $table->unsignedInteger('from_folder_id')->nullable();
            $table->unsignedInteger('to_folder_id')->nullable();
            $table->unsignedInteger('moved_by');
            $table->timestamp('moved_at')->nullable();
            $table->timestamps();
            $table->index('document_id');
            $table->index('from_folder_id');
            $table->index('to_folder_id');
            $table->index('moved_by');

            $table->index(['document_id', 'moved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_logs');
    }
};
