<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
       Schema::create('reports', function (Blueprint $table) {
            $table->increments('report_id');
            $table->string('reason', 255);
            $table->enum('status', ['new', 'processing', 'resolved'])->default('new');
            $table->unsignedInteger('document_id');
            $table->unsignedInteger('user_id');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
