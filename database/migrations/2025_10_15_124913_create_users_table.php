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
        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('name', 150);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->unsignedInteger('role_id');
            $table->boolean('status')->default(0);
            $table->timestamps();

            $table->index('name', 'idx_users_name');
            $table->index('email', 'idx_users_email');
            $table->index('role_id', 'idx_users_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
