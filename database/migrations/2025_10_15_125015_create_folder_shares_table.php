<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folder_shares', function (Blueprint $table) {
            $table->increments('share_id');
            $table->unsignedInteger('folder_id');
            $table->unsignedInteger('owner_id');
            $table->unsignedInteger('shared_with_id');
            $table->enum('permission', ['view', 'edit'])->default('view');
            // ✅ SỬA: Bỏ 'after()' vì MariaDB không hỗ trợ
            $table->boolean('inherit_to_subfolders')->default(true);
            $table->boolean('inherit_to_documents')->default(true);
            $table->timestamps();

            // Khóa ngoại
            $table->foreign('folder_id')->references('folder_id')->on('folders')->onDelete('cascade');
            $table->foreign('owner_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('shared_with_id')->references('user_id')->on('users')->onDelete('cascade');

            // Index
            $table->unique(['folder_id', 'shared_with_id'], 'idx_folder_share_unique');
            $table->index('shared_with_id', 'idx_folder_shared_with');
            $table->index('owner_id', 'idx_folder_owner');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folder_shares');
    }
};
