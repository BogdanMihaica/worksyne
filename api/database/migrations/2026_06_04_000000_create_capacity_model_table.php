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
        if (! Schema::hasTable('capacity_model')) {
            Schema::create('capacity_model', function (Blueprint $table) {
                $table->id();

                $table->foreignId('company_id');
                $table->foreignId('workstream_id');
                $table->enum('seniority', ['intern', 'junior', 'mid', 'senior'])->default('intern');
                $table->unsignedSmallInteger('units_per_hour')->default(0);

                $table->timestamps();

                $table->unique(['company_id', 'workstream_id', 'seniority'], 'capacity_model_company_workstream_seniority_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('capacity_model');
    }
};
