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
        Schema::rename('users', 'user');

        // User table column additions
        Schema::table('user', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_email_verified')->default(false);
            $table->boolean('is_blocked')->default(false);
        });

        // API authentication tokens
        Schema::create('auth_token', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id');
            $table->string('name')->nullable();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
        });

        // Company tables
        Schema::create('company', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_plan_id')->nullable();

            $table->string('name')->unique();
            $table->foreignId('owner_id');

            $table->timestamps();
        });

        Schema::create('workstream', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id');
            $table->string('name');

            $table->timestamps();
        });

        Schema::create('company_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id');
            $table->foreignId('user_id');

            $table->enum('role', ['company_admin', 'team_lead', 'worker'])->default('worker');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->timestamps();
        });

        Schema::create('company_user_seniority', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id');
            $table->foreignId('user_id');
            $table->foreignId('workstream_id');

            $table->enum('seniority', ['intern', 'junior', 'mid', 'senior'])->default('intern');

            $table->timestamps();
        });

        // Pivot table for users and workstreams they have worked on
        Schema::create('user_workstream', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id');
            $table->foreignId('workstream_id');
            $table->string('unique_code')->unique();
            $table->unsignedSmallInteger('units')->default(1);

            $table->timestamps();
        });

        // Subscription plans table
        Schema::create('subscription_plan', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->decimal('price', 8, 2);

            $table->timestamps();
        });

        // Features table
        Schema::create('feature', function (Blueprint $table) {
            $table->id();

            $table->string('name')->unique();
            $table->string('key')->unique();
            $table->text('description')->nullable();

            $table->timestamps();
        });

        // Pivot table for subscription plans and features
        Schema::create('subscription_plan_feature', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_plan_id');
            $table->foreignId('feature_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropAllTables();
    }
};
