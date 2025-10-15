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
        Schema::create('document_accesses', function (Blueprint $table) {
            $table->increments('access_id');
            $table->string('share_link', 255)->nullable()->unique();
            $table->enum('granted_to_type', ['user', 'role', 'link'])->default('user');
            $table->boolean('can_view')->default(0);
            $table->boolean('can_edit')->default(0);
            $table->boolean('can_delete')->default(0);
            $table->boolean('can_upload')->default(0);
            $table->boolean('can_download')->default(0);
            $table->boolean('can_share')->default(0);
            $table->timestamp('expiration_date')->nullable();
            $table->unsignedInteger('document_id');
            $table->unsignedInteger('granted_by');
            $table->unsignedInteger('granted_to_user_id')->nullable();
            $table->unsignedInteger('granted_to_role_id')->nullable();
            $table->timestamps();
            $table->index('document_id');
            $table->index('granted_by');
            $table->index('granted_to_user_id');
            $table->index('granted_to_role_id');
            $table->index('expiration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_accesses');
    }
};
