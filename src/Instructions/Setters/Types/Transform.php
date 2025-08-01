<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Process\Errors\EzportSetterException;
use Go2Flow\Ezport\Process\Jobs\AssignTransform;
use Go2Flow\Ezport\Process\Jobs\Transform as TransformJob;
use Go2Flow\Ezport\Models\GenericModel;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;
use Go2Flow\Ezport\Instructions\Setters\Special\Relation;

class Transform extends Basic
{
    protected ?\closure $prepare = null;
    protected Collection $processes;
    protected Collection $relations;
    protected ?\closure $config = null;
    protected ?Collection $items;
    protected bool $shouldSave = true;
    protected int $chunk = 50;

    public function __construct(string $key, array $config = [])
    {
        parent::__construct($key);

        foreach (['prepare', 'process'] as $type) {
            if (isset($config[$type])) {
                if (!is_callable($config[$type])) throw new \Exception("{$type} must be a closure");

                $this->$type = $config[$type];
            }
        }

        $this->job = (new Job)->class(AssignTransform::class);

        $this->processes = collect();
        $this->relations = collect();
    }
    /**
     * the prepare closure must return an instance of Builder or Collection
     * if you return a collection, it must be a collection of GenericModel ids (not unique_ids)
     * If you return a Query Builder the program will check if 'updated' and 'touched' are set to true
     */

    public function prepare(\closure $prepare) : self
    {
        $this->prepare = $prepare;

        return $this;
    }

    /**
     * each generic object created in the prepare closure will be passed to the process closure individually
     * if you don't have a prepare closure, the process closure will be called without parameters
     */

    public function process(\closure $process) : self
    {
        $this->processes->push($process);

        return $this;
    }

    public function processes(array $processes) : self
    {
        $this->processes = collect($processes);

        return $this;
    }

    public function relation(\closure $relation) : self
    {
        $this->processes->push($relation);

        return $this;
    }

    public function relations(array $relations) : self
    {
        $this->relations = collect($relations);

        return $this;
    }

    public function dontSave() : self
    {
        $this->shouldSave = false;
        return $this;
    }

    public function config(\closure $config) : self
    {
        $this->config = $config;

        return $this;
    }

    public function chunk(int $chunk) : self
    {
        $this->chunk = $chunk;

        return $this;
    }

    public function getJobs()
    {
        $config = $this->config ? ($this->config)() : collect();

        return !$this->items
            ? collect([new TransformJob($this->project->id, $this->key->toString(), null, $config) ])
            : $this->items->chunk($this->chunk)
                ->map(
                    fn ($chunk) => new TransformJob(
                        $this->project->id,
                        $this->key->toString(),
                        $chunk,
                        $config
                    )
                );
    }

    public function pluck(): self
    {
        if (! $this->prepare) {
            $this->items = null;
            return $this;
        }

        $instruction = ($this->prepare)();

        if (! $instruction instanceof Builder && ! $instruction instanceof Collection) {

            throw new EzportSetterException("the prepare closure must return a collection or a query builder");
        }

        $this->items = $instruction instanceof Builder
            ? $instruction->whereTouched(true)
                ->whereUpdated(true)
                ->pluck('id')
            : $instruction;

        return $this;
    }

    public function run(?Collection $chunk, array|Collection $config = []): void
    {
        $chunk
            ? GenericModel::whereProjectId($this->project->id)
            ->whereIn('id', $chunk)
            ->get()
            ->toContentType()
            ->each(
                fn ($item) => $this->runThroughFunctionality($item, $config)
            )
            : $this->runThrough('processes', null, $config);
    }

    private function runThroughFunctionality(?Generic $item, array|Collection $config) : void
    {
        $relations = $this->runThrough('relations', $item, $config);
        $this->runThrough('processes', $item, $config);

        $item->relations(
            $relations?->filter(
                fn ($relation) => $relation->filter(fn ($item) => $item)->count() > 0
            )
        );

        if(! $this->shouldSave) return;

        $item->updateOrCreate()
            ->setRelations();

    }

    private function runThrough(string $name, ?Generic $item, array|Collection $config) : Collection
    {
        return $this->$name->flatMap(
            fn ($closure) => $closure instanceof Relation
                ? $closure->setProject($this->project)->process($item, $config)
                : $closure($item, $config)
        );
    }
}
