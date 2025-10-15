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
        Schema::create('version_comparisons', function (Blueprint $table) {
            $table->increments('comparison_id');
            $table->string('diff_result', 255)->nullable();
            $table->unsignedInteger('base_version_id');
            $table->unsignedInteger('compare_version_id');
            $table->unsignedInteger('compared_by')->nullable();
            $table->timestamps();
            $table->index('base_version_id');
            $table->index('compare_version_id');
            $table->index('compared_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version_comparisons');
    }
};
