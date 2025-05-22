<?php

namespace Go2Flow\Ezport\Models;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Process\Errors\CircularRelationException;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
     * Go2Flow\Ezport\Models\GenericModel
     * @property int $id
     * @property int $project_id
     * @property string $unique_id
     * @property string $type
     * @property string $name
     * @property bool $updated
     * @property bool $touched
     * @property ?Collection $content
     * @property ?Collection shop
     * @property Pivot $pivot
     */

class GenericModel extends BaseModel
{
    use HasFactory;

    public $modelRelations;

    protected $guarded = [];

    protected $casts = [
        'content' => AsCollection::class,
        'shop' => AsCollection::class,
    ];

    public function processRelations(): void
    {
        if (!$this->content) return;

        foreach ($this->content as $key  => $item) {
            if (!($string = Str::of($key))->contains('_id')) continue;

            $type = Content::type($string->before('_id')->camel()->ucfirst(), Project::find($this->project_id))
                ->whereIn('unique_id', collect($item))
                ->get();

            if ($type->count() == 0) continue;

            $this->getOrSetData([(string) $string->before('_id')->plural() => $type], 'modelRelations');

            $this->content->forget($key);
        }
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(
            GenericModel::class,
            'nested_relationships',
            'parent_id',
            'child_id'
        )->withPivot('group_type');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(
            GenericModel::class,
            'nested_relationships',
            'child_id',
            'parent_id'
        )->withPivot('group_type');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(
            Project::class,
        );
    }

    public function updateOrCreateModel($updated) : array
    {
        if (is_string($updated)) {
            $updated = collect(['touch', 'update', 'touched', 'updated'])->contains(Str::lower($updated));
        }

        $this->updated = $this->updated || $this->isDirty();
        if ($updated) $this->touched = true;

        $original = $this->getOriginal();
        $exists = $this->exists;

        if ($name = $this->content?->filter(fn ($item, $key) => Str::lower($key) === 'name')?->first()){
            $this->name = $name;
        }

        $this->save();

        if ($this->unique_id === null) {
            $this->update(['unique_id' => $this->id . '-' . $this->type]);
        }

        return [$original, $exists];
    }

    public function toContentType(array $getDescendants = [true, false]): Generic
    {
        $class = new Generic($this);
        if (array_shift($getDescendants)) $class->relations($this->arrangeChildren($getDescendants));

        return $class;
    }

    public function setContentAndRelations($data) : self
    {
        foreach ($data as $key => $item) {
            $field = ($item instanceof Collection || is_array($item)) && collect($item)->first() instanceof Generic
                ? 'modelRelations'
                : 'content';

            $this->$field =  (!$this->$field)
                ? collect([$key => $item])
                : $this->$field->merge([$key => $item]);
        }

        return $this;
    }

    public function findOrCreateModel($data): self
    {
        if (isset($data['unique_id'])) {

            $model = $this->whereType($data['type'] )
                ->whereUniqueId($data['unique_id'])
                ->whereProjectId($data['project_id'])
                ->first();

            if ($model) return $model;
        }

        return $this->fill([
            'unique_id' => $data['unique_id'] ?? null,
            'type' => $data['type'],
            'project_id' => $data['project_id'],
        ]);
    }

    public function assertNoCircularRelation(GenericModel $child): void
    {
        $visited = [$child->id];
        $stack = $child->children;

        while ($stack->isNotEmpty()) {
            $current = $stack->pop();

            if ($current->id === $this->id) {
                throw new CircularRelationException(
                    "Circular relationship detected when attempting to attach model {$child->id} to {$this->id}."
                );
            }

            if (!in_array($current->id, $visited)) {
                $visited[] = $current->id;
                $stack = $stack->merge($current->children);
            }
        }
    }

    private function arrangeChildren(array $getDescendants): Collection
    {
        return $this->children->groupBy(
            fn ($child) => $child->pivot->group_type
        )->map(
            fn ($group) => $group->toContentType($getDescendants)
        );
    }
}
