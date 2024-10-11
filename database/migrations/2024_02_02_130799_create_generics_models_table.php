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
        Schema::create('generic_models', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->nullable();
            $table->boolean('updated')->default(true);
            $table->boolean('touched')->default(true);
            $table->string('type');
            $table->longText('content')->nullable();
            $table->longText('shop')->nullable();
            $table->string('name')->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->oncascade('delete');
            $table->index('type');
            $table->index('unique_id');
            $table->index(['unique_id', 'type']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generics');
    }
};
