<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Closure;
use Go2Flow\Ezport\Instructions\Getters\GetProxy;
use Go2Flow\Ezport\Instructions\Interfaces\ImportInstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\Assignable;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Jobs\AssignInstruction;
use Go2Flow\Ezport\Process\Jobs\ModifyModel;
use Illuminate\Support\Collection;

class Model extends Basic implements ImportInstructionInterface, JobInterface, Assignable
{

    protected Collection $getters;
    protected UploadProcessor|GetProxy|null|string $processor = null;
    protected ?closure $items = null;
    protected array $instructions = [];

    public function __construct(string $key)
    {
        parent::__construct($key);
        $this->job = Set::job()
            ->class(AssignInstruction::class);
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

    public function type(string $type) : self
    {
        $this->job = $this->job->config(['type' => $type]);

        return $this;

    }

    public function instructions(array $instructions) : self
    {
        $this->instructions = $instructions;

        return $this;

    }

    public function assignJobs(): Collection
    {
        return ($this->items)()
            ->chunk(100)
            ->map(
                fn ($chunk) => new ModifyModel(
                    $this->project->id,
                    $this->instructions,
                    $chunk->toArray()
                )
            );
    }
}
