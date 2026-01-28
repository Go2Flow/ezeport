<?php

namespace Go2Flow\Ezport\Process\Batches\Tools;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Types\Upload;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Collection;

class UploadManager
{
    public Project $project;
    private Collection $batch;
    private Collection $all;

    public function __construct(Project $project)
    {
        $this->all = collect();
        $this->project = $project;
    }

    public function getAll(): Collection
    {
        return $this->all->unique()->values();
    }

    public function getBatch(): Collection
    {
        return $this->batch;
    }

    public function batch(Collection|array $items): self
    {
        $this->batch = $this->prepareBatchWithinstruction($items);

        return $this;
    }

    private function prepareBatchWithInstruction(Collection|array $items, $instructions = null) : Collection
    {
        if (!$instructions) $instructions = Find::instruction($this->project, 'upload');

        return collect($items)->flatmap(
            function ($key) use ($instructions) {
                if (is_array($key) || $key instanceof Collection) return [$this->prepareBatchWithInstruction($key, $instructions)->toArray()];

                $instruction = $instructions->find($key);

                if (! $instruction instanceof Upload) return [$instruction->getJob()];

                $pluck = $instruction->pluck();

                $this->all->push(...$pluck->flatten());

                return $pluck->map(
                    fn ($chunk) => $instruction->getJob([
                        'items' => $chunk,
                        'key' => $key
                    ])
                );
            }
        );
    }
}
