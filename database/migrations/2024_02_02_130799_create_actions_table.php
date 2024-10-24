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
        if (!Schema::hasTable('actions')) {

            Schema::create(
                'actions',
                function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('project_id')
                        ->constrained()
                        ->cascadeOnDelete();
                    $table->string('name');
                    $table->string('type');
                    $table->boolean('active')
                        ->default(true);
                    $table->timestamps();
                    $table->dateTime('finished_at')
                        ->nullable();
                    $table->string('queue')->nullable();
                    $table->string('step')->nullable();
                }
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actions');
    }
};
