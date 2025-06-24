<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Closure;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Getters\GetProxy;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Jobs\AssignClean;
use Go2Flow\Ezport\Process\Jobs\CleanWithInstruction;
use Illuminate\Support\Collection;
use \Go2Flow\Ezport\Models\Project;

class Clean extends Basic implements JobInterface
{

    protected Collection $getters;
    protected UploadProcessor|GetProxy|null|string $processor = null;
    protected ?closure $ids = null;
    protected Collection $items;
    protected array $config = [];
    protected array $components = [];
    protected int $chunk = 25;
    protected ?closure $process;
    protected ?GetProxy $api = null;
    protected bool $showNull = false;
    protected string $type;

    public function __construct(string $key)
    {
        parent::__construct($key);
        $this->job = Set::job()
            ->class(AssignClean::class);
    }

    /**
     * The items closure must return either a collection or a builder.
     * In the case of a builder, the program will add that updated and touched must be true.
     * In the case of a collection, this collection should only contain ids (e.g. pluck).
     */

    public function items(closure $items): self
    {

        $this->ids = $items;

        return $this;
    }

    public function api(GetProxy|Api|string $api) : self {

        $this->api = (is_string($api))
            ? Get::api($api)
            : $api;

        return $this;
    }

    public function process(closure $closure) : self
    {
        $this->process = $closure;

        return $this;
    }

    /** add items to config so that it is available to the fields */

    public function config(array $config): self
    {

        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Size of the chunk to be passed to a job (default 25). Shrink this if jobs are timing out.
     */

    public function chunk(int $chunk) {

        $this->chunk = $chunk;

        return $this;
    }

    public function prepareItems() : self
    {
        $this->items = ($this->ids)();

        return $this;
    }

    public function getCleaner(): self
    {
        return $this;
    }

    public function prepareJobs(Project $project) : Collection
    {
        return $this->items->chunk($this->chunk)
        ->map(
            fn ($chunk) => new CleanWithInstruction(
                $project->id,
                $this->key->toString(),
                $chunk,
            )
        );
    }

    public function processBatch(Collection $chunk) : void {
        ($this->process)(
            $chunk,
            $this->api ?? Find::api($this->project, 'ShopSix')
        );
    }
}
