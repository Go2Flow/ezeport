<?php

namespace Go2Flow\Ezport\ContentTypes\Helpers;

// non-exhaustive list of methods that are available on the query builder (you can use any QueryBuilder method)

/**
 * @method Builder make(array $attributes = [])
 * @method Builder whereKey($id)
 * @method Builder whereKeyNot($id)
 * @method Builder create(Collection|array $attributes)
 * @method Builder where($column, $operator = null, $value = null, $boolean = 'and')
 * @method Builder firstWhere($column, $operator = null, $value = null, $boolean = 'and')
 * @method Builder orWhere($column, $operator = null, $value = null)
 * @method Builder whereIn($column, $values, $boolean = 'and', $not = false)
 * @method Builder whereNot($column, $operator = null, $value = null, $boolean = 'and')
 * @method Builder orWhereNot($column, $operator = null, $value = null)
 * @method Builder orWhereIn($column, $values)
 * @method Builder latest($column, $values, $boolean = 'and')
 * @method Builder oldest($column = null)
 * @method Builder findMany($ids, $columns = ['*'])
 * @method Builder findOrFail($id, $columns = ['*'])
 * @method Builder findOrNew($id, $columns = ['*'])
 * @method Builder findOr($id, $columns = ['*'], Closure $callback = null)
 * @method Builder firstOrCreate(array $attributes = [], array $values = [])
 * @method Builder updateOrCreate(array $attributes, array $values = [])
 * @method Builder firstOrFail($columns = ['*'])
 * @method Builder firstOr($columns = ['*'], Closure $callback = null)
 * @method Builder get($columns = ['*'])
 * @method Builder select($columns = ['*'])
 * @method Builder addSelect($column)
 * @method Builder selectRaw($expression, array $bindings = [])
 * @method Builder selectSub($query, $as)
 * @method Builder whereRaw($sql, $bindings = [], $boolean = 'and')
 * @method Builder orWhereRaw($sql, $bindings = [])
 * @method Builder whereNull($columns, $boolean = 'and', $not = false)
 * @method Builder whereNull($columns, $boolean = 'and', $not = false)
 * @method BuildsQueries first($column, $operator = null, $value = null, $boolean = 'and')
 * @method BuildsQueries chunk($count, callable $callback)
 */

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportContentTypeException;
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Query\Builder as QueryBuilder;

class TypeGetter implements BuilderContract
{
    private $query;

    public function __construct(
        private readonly string $type,
        private readonly Project $project
    )
    {
        $this->query = null;
    }

    public function query(): Builder
    {
        return $this->querySetter();
    }

    /**
     * Where the content type is on shopware
     */
    public function whereOnShop() : self
    {
        $this->query = $this->querySetter()->whereNot('shop', '[]');

        return $this;
    }

    /**
     * find by unique_id
     */
    public function find(int|string $id, $columns = ['*']): ?Generic
    {
        return $this->querySetter()->firstWhere('unique_id', $id)?->toContentType();
    }

    /**
     * find by id
     */
    public function findById(?int $id): ?Generic
    {
        return $this->querySetter()->find($id)?->toContentType();
    }

    public function findByName(string $name) : ?Generic
    {
        return $this->querySetter()->firstWhere('name', $name)?->toContentType();
    }

    /** like a standard model create, but you don't ne`ed to set 'type' and 'project_id' as that was already set in the constructor */

    public function create(array|Collection $attributes = []): Generic
    {

        if (Content::type($this->type, $this->project)->find($attributes['unique_id'] ?? null) ===  null)
        {
            return GenericModel::create(
                collect($attributes)->merge([
                    'type' => $this->type,
                    'project_id' => $this->project->id
                ])->toArray()
            )->toContentType();
        }

        throw new EzportContentTypeException(
            "The project of {$this->project->name} already has a model of type {$this->type} with the unique_id of {$attributes['unique_id']}. This combination must be unique."
        );
    }

    public function firstOrNew(string $unique_id) : Generic {

        if ($original = Content::type($this->type, $this->project)->find($unique_id)) return $original;

        return new Generic([
            'unique_id' => $unique_id,
            'project_id' => $this->project->id,
            'type' => $this->type
        ]);
    }

    public function updateOrCreate(array $attributes, array $values) : Generic {

        $values = $this->setPropertiesToContent($values);

        if (! $original = Content::type($this->type, $this->project)->find($attributes['unique_id'])) {
            return $this->create(
                array_merge(
                    $values,
                    $attributes
                )
            );
        }

        $original->properties($values['content'] ?? []);
        $original->shop($values['shop'] ?? []);

        return $original->updateOrCreate();
    }

    public function __call($method, array|null $parameters)
    {
        if (method_exists($this, $method)) return $this->$method(...$parameters);

        if ($response = $this->checkIfExistsOnQueryBuilder($method, $parameters)) return $response;

        if (Str::startsWith($method, 'where')) {

            $this->query = $this->querySetter()->dynamicWhere($method, $parameters);

            return $this;
        }

        throw new EzportContentTypeException('Method ' . $method . ' does not exist');
    }

    private function checkIfExistsOnQueryBuilder(string $name, ?array $arguments): mixed
    {
        if (!$this->checkExistence($name)) return false;

        $response = $this->querySetter()->$name(...$arguments);

        if ($response instanceof Generic) return $response;
        if ($response instanceof GenericModel || $response instanceof Collection) return $response->toContentType();

        if ($response instanceof BuilderContract) $this->query = $response;

        return $this;
    }

    private function checkExistence(string $name): bool
    {
        foreach ([Builder::class, BuildsQueries::class, QueryBuilder::class] as $class) {
            if (method_exists($class, $name)) return true;
        }

        return false;
    }

    private function querySetter() : Builder
    {
        if (! $this->query) {

            $this->query = GenericModel::where('type', $this->type)
                ->where('project_id', $this->project->id);
            }

        return $this->query;
    }

    private function setPropertiesToContent(array $values) : array
    {
        if (isset($values['properties'])) {
            $values['content'] = $values['properties'];
            unset($values['properties']);
        }

        return $values;

    }
}
