<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Closure;
use Go2Flow\Ezport\Instructions\Getters\GetProxy;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Jobs\ModifyModel;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Queue\ShouldQueue;


class Model extends Basic implements JobInterface
{

    protected Collection $getters;
    protected UploadProcessor|GetProxy|null|string $processor = null;
    protected ?closure $items = null;
    protected array $instructions = [];

    public function __construct(string $key)
    {
        parent::__construct($key);
        $this->job = Set::job()
            ->class(ModifyModel::class);
    }

    /**
     * The items closure must return either a collection or a builder.
     * In the case of a builder, the program will add that updated and touched must be true.
     * In the case of a collection, this collection should only contain ids (e.g. pluck).
     */

    public function items(closure $items): self
    {

        $this->items = $items;

        return $this;
    }

    public function instructions(array $instructions) : self
    {
        $this->instructions = $instructions;

        return $this;

    }

    public function getJob(array $content = []) : ShouldQueue
    {
        return new ($this->job->getJob())(
            $this->project->id,
            $this->instructions,
            $content['chunk']
        );

    }

    public function getJobs() : Collection
    {
        return ($this->items)()
            ->chunk(100)
            ->map(
                fn ($chunk) => $this->getJob(['chunk' => $chunk])
            );
    }
}
