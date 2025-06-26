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
        Schema::table('generic_models', function (Blueprint $table) {
            $table->nullableMorphs('morph');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generic_models', function (Blueprint $table) {
            $table->dropColumn('morph_id');
            $table->dropColumn('morph_type');
        });
    }
};
