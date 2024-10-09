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
        
        Schema::create('nested_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->cascadeOnDelete();
            $table->foreignId('child_id')->cascadeOnDelete();
            $table->string('group_type');
            $table->index('group_type'); 
            $table->index(['group_type', 'child_id']); 
            $table->index('child_id'); 
            $table->index('parent_id'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nested_relationships');
        
    }
};
