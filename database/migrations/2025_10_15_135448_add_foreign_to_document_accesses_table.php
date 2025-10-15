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
        Schema::table('document_accesses', function (Blueprint $table) {
            $table->foreign('document_id')->references('document_id')->on('documents')->onDelete('cascade');
            $table->foreign('granted_by')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('granted_to_user_id')->references('user_id')->on('users')->nullOnDelete();
            $table->foreign('granted_to_role_id')->references('role_id')->on('roles')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('document_accesses', function (Blueprint $table) {
            $table->dropForeign(['document_id']);
            $table->dropForeign(['granted_by']);
            $table->dropForeign(['granted_to_user_id']);
            $table->dropForeign(['granted_to_role_id']);
        });
    }
};
