<?php

namespace Go2Flow\Ezport\Process\Import\Xml;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Jobs\ProcessInstruction;
use Illuminate\Contracts\Database\Query\Builder;

class Transformer
{
    private $instructions;
    private $items;

    public function __construct(private Project $project, private string $type)
    {
        $this->instructions = Find::instruction($this->project, 'Transform')->byKey($type);
    }

    public function pluck(): self
    {

        $this->items = ($instruction = $this->instructions->items()) instanceof Builder
            ? $instruction->whereUpdated(true)->whereTouched(true)->pluck('id')
            : $instruction;

        return $this;
    }

    public function prepare()
    {
        return $this->items->chunk(25)
            ->map(
                fn ($chunk) => new ProcessInstruction(
                    $this->project->id,
                    ['instructionType' => 'transform', 'key' => $this->type, 'chunk' => $chunk]
                )
            );
    }

    public function process($chunk): void
    {
        GenericModel::whereProjectId($this->project->id)
            ->whereIn('id', $chunk)
            ->get()
            ->toContentType()
            ->each(
                fn ($item) => $this->instructions->process($item)
            );
        }
}
