<?php

namespace Go2Flow\Ezport\ContentTypes\Helpers;

// non-exhaustive list of methods that are available on the query builder (you can use any QueryBuilder method)

/**
 * @method Builder make(array $attributes = [])
 * @method Builder whereKey($id)
 * @method Builder whereKeyNot($id)
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
use Illuminate\Contracts\Database\Query\Builder as BuilderContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Query\Builder as QueryBuilder;

class TypeGetter implements BuilderContract
{
    private $query;

    public function __construct(string $type, Project $project)
    {
        $this->query = GenericModel::where('type', $type)
            ->with('children')
            ->where('project_id', $project->id);
    }

    public function query(): Builder
    {
        return $this->query;
    }

    /**
     * Where the content type is on shopware
     */

    public function whereOnShop(): self
    {
        return $this->query->whereNot('shop', '[]');

    }

    /**
     * find by unique_id
     * @param int|string $id
     * @param string[] $columns
     */

    public function find(int|string $id, $columns = ['*']): ?Generic
    {
        return $this->query->firstWhere('unique_id', $id)?->toContentType();
    }

    /**
     * find by id
     */

    public function findById(?int $id): ?Generic
    {
        return $this->query->find($id)?->toContentType();
    }

    public function __call($method, array|null $parameters)
    {
        if (method_exists($this, $method)) return $this->$method(...$parameters);

        if ($response = $this->checkIfExistsOnQueryBuilder($method, $parameters)) return $response;

        if (Str::startsWith($method, 'where')) {

            $this->query = $this->query->dynamicWhere($method, $parameters);

            return $this;
        }

        throw new \Exception('Method ' . $method . ' does not exist');
    }

    private function checkIfExistsOnQueryBuilder(string $name, ?array $arguments): mixed
    {
        if (!$this->checkEsistence($name)) return false;

        $response = $this->query->$name(...$arguments);

        if ($response instanceof Generic) return $response;
        if ($response instanceof GenericModel || $response instanceof Collection) return $response->toContentType();

        if ($response instanceof BuilderContract) $this->query = $response;

        return $this;
    }

    private function checkEsistence($name): bool
    {
        foreach ([Builder::class, BuildsQueries::class, QueryBuilder::class] as $class) {
            if (method_exists($class, $name)) return true;
        }

        return false;
    }
}
