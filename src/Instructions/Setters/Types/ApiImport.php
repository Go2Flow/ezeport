<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Closure;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Getters\GetProxy;
use Go2Flow\Ezport\Instructions\Interfaces\ImportInstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Jobs\AssignProcess;
use Go2Flow\Ezport\Process\Jobs\RunProcess as RunProcessJob;
use Illuminate\Support\Collection;

class ApiImport extends Basic implements ImportInstructionInterface {

    protected string $type;
    protected string $uniqueId;
    protected closure $items;
    protected ?closure $process;
    protected Collection $properties;
    protected Collection $shop;
    protected GetProxy $api;
    protected int $chunk;

    public function __construct(string $key, private array $config = [])
    {
        parent::__construct($key);
        $this->type = $this->key;

        $this->properties = collect();
        $this->shop = collect();

        $this->items = fn () => null;
        $this->process = fn () => null;
        $this->chunk = 25;

        $this->job = Set::Job()
            ->class(AssignProcess::class);

    }

    /**
     * Set the Api. Use the Find static method or Api class.
     */

    public function api(GetProxy|Api|string $api) : self {

        $this->api = (is_string($api))
            ? Get::api($api)
            : $api;

        return $this;
    }

    /**
     * get all the items that need to be imported. The closure should return a collection of ids.
     * These will then be individually called and processed in the 'process' closure.
     * If you don't call this method then the process closure will be called with an empty collection.
     */

    public function items(Closure $items) : self
    {
        return $this->setProperty('items', $items);
    }

    /** set the number of items that will be passed from the 'items' closure to the 'process' closure  */

    public function chunk(int $chunk) : self
    {
        return $this->setProperty('chunk', $chunk);
    }

    public function getJobs() : Collection
    {
        $items = ($this->items)(($this->api)($this->project));

        return $items->chunk($this->chunk)
            ->map(
                fn ($chunk) => new RunProcessJob(
                    $this->project->id,
                    ['items' => $chunk, 'type' => 'Import', 'key' => $this->key]
                )
            );
    }
}
