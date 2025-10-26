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
        Schema::create('folders', function (Blueprint $table) {
            $table->increments('folder_id');
            $table->string('name', 255)->unique();
            $table->enum('status', ['public', 'private'])->default('private');
            $table->unsignedInteger('parent_folder_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->timestamps();

            $table->index(['user_id', 'parent_folder_id'], 'idx_folder_user_parent');
            $table->index('status', 'idx_folder_status');
            $table->index(['user_id', 'created_at'], 'idx_folder_user_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
