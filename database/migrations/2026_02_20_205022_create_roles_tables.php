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
        Schema::create('roles', static function (Blueprint $table) {
            $table->ulid('id')->primary();

            $table->string('name');
            $table->string('context');
            $table->string('description')->nullable();
            $table->json('permissions')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('role_users', static function (Blueprint $table) {
            $table->foreignUlid('role_id')->constrained('roles');
            $table->foreignUlid('user_id')->constrained('users');
            $table->foreignUlid('account_id')->nullable()->constrained('accounts');

            $table->primary(['role_id', 'user_id', 'account_id']);
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_users');
        Schema::dropIfExists('roles');
    }
};
