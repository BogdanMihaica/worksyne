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
        Schema::create('timelog', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();

            $table->timestamps();
        });

        Schema::create('timelog_break', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('timelog_id');
            
            $table->text('note')->nullable();
            $table->timestamp('start_time');
            $table->timestamp('end_time')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timelog_break');
        Schema::dropIfExists('timelog');
    }
};
