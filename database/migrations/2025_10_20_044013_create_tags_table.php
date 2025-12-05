<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->increments('tag_id');

            // Tên tag (unique) – giữ nguyên 150, hợp lý
            $table->string('name', 150)->unique();

            // Mô tả – tăng lên 500 để phòng copy/paste HTML dài
            $table->string('description', 500)->nullable();

            // Upload ảnh (phục vụ test 11–12–13)
            $table->string('image_path', 255)->nullable();

            // Status
            $table->boolean('status')->default(0);

            // Soft delete để xử lý test-case 1 & 14
            $table->softDeletes();

            // timestamps → phục vụ optimistic lock (test-case 2)
            $table->timestamps();

            // Index
            $table->index('name');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
