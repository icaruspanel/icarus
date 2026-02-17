<?php

use Illuminate\Database\Migrations\Migration;
use Tpetry\PostgresqlEnhanced\Schema\Blueprint;
use Tpetry\PostgresqlEnhanced\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', static function (Blueprint $table) {
            $table->id();

            // Normal user stuff
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');

            // This is never used but has to be here because Laravel auth
            // is crapsail a
            $table->rememberToken();

            // Flag that allows the user to access the admin area
            $table->boolean('is_admin')->default(false);

            $table->timestamp('email_verified_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('accounts', static function (Blueprint $table) {
            $table->id();

            $table->string('name')->nullable();
            $table->string('identifier')->unique();

            // The status is arbitrary
            $table->string('status')->default('active');

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('account_users', static function (Blueprint $table) {
            $table->id();

            // Account users always belong to both an account and a user
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('user_id')->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['account_id', 'user_id']);
        });

        Schema::create('roles', static function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('description')->nullable();

            // Roles are either 'user' or 'admin'
            $table->string('type');

            // Permissions are defined in code, so they can stored in a nice
            // JSON array rather than in relations.
            $table->json('permissions')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_roles', static function (Blueprint $table) {
            $table->id();

            $table->foreignId('role_id')->constrained('roles');
            $table->foreignId('user_id')->constrained('users');

            // Sometimes a user role can be account-specific
            $table->foreignId('account_id')->nullable()->constrained('accounts');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['role_id', 'user_id', 'account_id']);
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
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('account_users');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_password_reset_tokens');
    }
};
