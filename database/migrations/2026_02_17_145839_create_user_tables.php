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
        Schema::create('users', static function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Normal user stuff
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->json('operates_in')->nullable();

            $table->boolean('active')->default(true);
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_password_reset_tokens', static function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('user_password_reset_tokens');
    }
};
