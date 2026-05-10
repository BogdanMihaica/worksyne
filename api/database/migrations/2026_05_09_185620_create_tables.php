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

        // Company tables
        Schema::create('company', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedBigInteger('owner_id');

            $table->timestamps();
        });

        Schema::create('workstream', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('company_id');
            $table->string('name');

            $table->timestamps();
        });
        
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');

            $table->enum('role', ['company_admin', 'team_lead', 'worker'])->default('worker');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

            $table->timestamps();
        });
        
        Schema::create('company_user_seniority', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('workstream_id');

            $table->enum('seniority', ['intern', 'junior', 'mid', 'senior'])->default('intern');

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

            $table->unsignedBigInteger('subscription_plan_id');
            $table->unsignedBigInteger('feature_id');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // nothing to do
    }
};
