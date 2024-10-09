<?php

namespace Go2Flow\Ezport\Upload;

use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Upload;
use Illuminate\Support\Collection;

class UploadManager
{
    public $project;
    private $batch;
    private $all;

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

    public function batch(array $data): self
    {
        $this->batch = $this->prepareBatchWithinstruction($data);

        return $this;
    }

    private function prepareBatchWithInstruction(array $data, $instructions = null)
    {

        if (!$instructions) $instructions = Find::instruction($this->project, 'upload');

        return collect($data)->flatmap(
            function ($key) use ($instructions) {
                if (is_array($key) || $key instanceof Collection) return [$this->prepareBatchWithInstruction($key)->toArray()];

                $instruction = $instructions->find($key);

                if (! $instruction instanceof Upload) return $instruction->getJob();

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
