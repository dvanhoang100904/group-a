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
        Schema::create('subjects', function (Blueprint $table) {
            $table->increments('subject_id');
            $table->string('name', 150)->unique();
            $table->string('description', 255)->nullable();
            $table->unsignedInteger('department_id');
            $table->timestamps();
            $table->index('department_id', 'idx_subject_department');
            $table->index(['department_id', 'created_at'], 'idx_subject_dept_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
