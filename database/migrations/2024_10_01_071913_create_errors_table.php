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
        if (!Schema::hasTable('errors')) {

            Schema::create('errors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('action_id')->nullable();
                $table->string('error_type');
                $table->longText('properties')->nullable();
                $table->longText('description');
                $table->string('level')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('errors');
    }
};
