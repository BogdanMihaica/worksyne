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
            $table->foreignId('owner_id')->nullable();

            $table->string('name')->unique();

            $table->timestamps();
        });

        Schema::create('workstream', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id');
            $table->string('name');

            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });

        Schema::create('company_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->nullable();
            $table->string('external_id')->nullable();

            $table->enum('role', ['company_admin', 'worker'])->default('worker');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->timestamps();
        });

        Schema::table('user', function (Blueprint $table) {
            $table->foreignId('company_user_id')->nullable()->unique()->after('id');
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
            $table->string('unique_code')->nullable()->unique();
            $table->unsignedSmallInteger('units')->default(1);
            $table->string('reference_code')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();
        });

        // Time off requests table
        Schema::create('timeoff_request', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('timezone')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

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

        Schema::create('order', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id');
            $table->foreignId('company_id');
            $table->foreignId('subscription_plan_id');

            $table->decimal('amount', 8, 2);
            $table->string('currency', 3);
            $table->string('external_id')->nullable();

            $table->enum('status', ['pending', 'paid', 'failed'])->default('pending');

            $table->timestamps();
        });

        Schema::create('subscription', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id');
            $table->foreignId('subscription_plan_id');

            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->enum('status', ['active', 'canceled', 'expired'])->default('active');

            $table->timestamps();
        });

        Schema::create('payment', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id');
            $table->foreignId('subscription_id')->nullable();

            $table->decimal('amount', 8, 2);
            $table->string('currency', 3);
            $table->string('external_id')->nullable();
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();

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
