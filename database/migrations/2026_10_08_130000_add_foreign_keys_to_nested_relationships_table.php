<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The original migration declared parent_id/child_id with ->cascadeOnDelete() but without
 * ->constrained(), so no foreign key exists. Deleting a GenericModel directly (not through
 * Generic::delete()) therefore left its relations behind; on long-running projects most rows
 * point to deleted models. Removes those rows and adds the intended cascading foreign keys.
 *
 * Run while no queue jobs are writing relations, otherwise a row orphaned between the cleanup
 * and the ALTER makes adding the key fail (re-running the migration fixes it).
 */
return new class extends Migration
{
    private const KEYS = [
        'parent_id' => 'nested_relationships_parent_id_foreign',
        'child_id' => 'nested_relationships_child_id_foreign',
    ];

    public function up(): void
    {
        $existing = collect(Schema::getForeignKeys('nested_relationships'))->pluck('name');

        foreach (self::KEYS as $column => $name) {
            if ($existing->contains($name)) {
                continue;
            }

            DB::table('nested_relationships')
                ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                    ->from('generic_models')
                    ->whereColumn('generic_models.id', 'nested_relationships.'.$column))
                ->delete();

            Schema::table('nested_relationships', function (Blueprint $table) use ($column, $name) {
                $table->foreign($column, $name)->references('id')->on('generic_models')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $existing = collect(Schema::getForeignKeys('nested_relationships'))->pluck('name');

        Schema::table('nested_relationships', function (Blueprint $table) use ($existing) {
            foreach (self::KEYS as $name) {
                if ($existing->contains($name)) {
                    $table->dropForeign($name);
                }
            }
        });
    }
};
