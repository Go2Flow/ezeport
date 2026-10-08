<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'actions_project_active_created_index';

    /**
     * Project::currentAction() runs on every Generic save (activity log). Without this index
     * MySQL filesorts all actions of the project per call (~0.25s at 47k rows).
     */
    public function up(): void
    {
        if (Schema::hasIndex('actions', self::INDEX)) {
            return;
        }

        Schema::table('actions', function (Blueprint $table) {
            $table->index(['project_id', 'active', 'created_at'], self::INDEX);
        });
    }

    public function down(): void
    {
        Schema::table('actions', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }
};
