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

    $table->string('code', 20)->unique()->nullable();

    $table->string('name', 150)->unique();
    $table->string('description', 500)->nullable();
    $table->string('image_path', 255)->nullable();
    $table->boolean('status')->default(0);

    $table->softDeletes();
    $table->timestamps();

    $table->index('name');
    $table->index('status');
    });

    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
