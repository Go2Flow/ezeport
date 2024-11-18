<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Import\Csv\Imports\Import;
use Go2Flow\Ezport\Process\Jobs\AssignProcess;
use Go2Flow\Ezport\Process\Jobs\RunProcess as RunProcessJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Go2Flow\Ezport\Models\Project;

class CsvImport extends Basic
{
    private string $file;

    private array $config = [];

    protected \Closure|null $process;

    private int $chunk = 25;

    public function __construct(string $key, array $config = [])
    {
        parent::__construct($key);

        $this->job = Set::Job()
            ->class(AssignProcess::class);
    }

    public function config(array $config) : self
    {
        $this->config = $config;

        return $this;
    }

    public function process(\Closure $closure) : self
    {
        $this->process = $closure;

        return $this;
    }

    public function file(string $file) : self
    {
        $this->file = $file;

        return $this;
    }

    public function chunk(int $chunk) : self
    {
        $this->chunk = $chunk;

        return $this;
    }

    public function getJobs($projectId) : Collection
    {
        $importer = new Import($this->config);

        return $importer->collection(
            $importer->toCollection(
                Storage::drive('public')
                    ->path(Project::find($projectId)->identifier . '/' . $this->file)
            )
        )->chunk($this->chunk)
        ->map(
            fn ($chunk) => new RunProcessJob(
                $projectId,
                ['items' => $chunk, 'type' => 'Import']
            )
        );
    }
}
