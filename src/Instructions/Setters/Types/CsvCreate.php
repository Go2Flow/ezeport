<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Import\Csv\Creates\Create;
use Go2Flow\Ezport\Process\Import\Csv\Imports\Import;
use Go2Flow\Ezport\Process\Jobs\AssignProcess;
use Go2Flow\Ezport\Process\Jobs\RunProcess as RunProcessJob;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CsvCreate extends Basic {

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

    public function chunk(int $chunk) : self
    {
        $this->chunk = $chunk;

        return $this;
    }

    public function getJobs() : Collection
    {
        $items = ($this->process)();

            $create = new Create($items);

            return $create->collection();
//        return $upload->collection(
//            $upload->toCollection(
//                Storage::drive('public')
//                    ->path($this->project->identifier . '/' . $this->file)
//            )
//        )->chunk($this->chunk)
//            ->map(
//                fn ($chunk) => new RunProcessJob(
//                    $this->project->id,
//                    ['items' => $chunk, 'type' => 'Import', 'key' => $this->key]
//                )
//            );
    }
}
