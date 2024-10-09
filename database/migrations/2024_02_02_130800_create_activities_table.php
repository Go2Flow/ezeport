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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('action_id')->nullable()->constrained('actions')->onDelete('cascade');
            $table->foreignId('generic_model_id')->nullable()->constrained('generic_models')->onDelete('cascade');
            $table->longText('properties')->nullable();
            $table->string('unique_id')->nullable();
            $table->string('generic_model_type')->nullable();
            $table->string('description')->nullable();
            $table->boolean('failed_job')->default(false);
            $table->string('activity_type')->default('generic_model');
            $table->index('unique_id');
            $table->index('action_id');
            $table->index('generic_model_id');
            $table->index('generic_model_type');
            $table->index('activity_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
