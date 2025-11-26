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
            $table->string('name', 255);
            $table->unsignedInteger('parent_folder_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'parent_folder_id'], 'idx_folder_user_parent');
            $table->index(['user_id', 'created_at'], 'idx_folder_user_created');

            // Foreign keys (nếu cần)
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('parent_folder_id')->references('folder_id')->on('folders')->onDelete('cascade');
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
