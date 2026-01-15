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
    private ?string $file = null;

    private ?string $folder = null;

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

    public function folder(string $folder) : self
    {
        $this->folder = $folder;

        return $this;
    }


    public function chunk(int $chunk) : self
    {
        $this->chunk = $chunk;

        return $this;
    }

    public function getJobs() : Collection
    {
        $importer = new Import($this->config);
        $disk = Storage::disk('public');

        return collect($this->fileAndFolder($disk))
            ->flatMap(fn (string $file) => $this->prepareJobs($importer, $disk, $file))
            ->values();
    }

    private function prepareJobs($importer, $disk, $file) : Collection {

        return $importer->collection(
            $importer->toCollection(
                $disk->path($file)
            )
        )->chunk($this->chunk)
            ->map(
                fn ($chunk) => new RunProcessJob(
                    $this->project->id,
                    ['items' => $chunk, 'type' => 'Import', 'key' => $this->key]
                )
            );
    }

    private function fileAndFolder($disk) : array {

        $array = [];

        if ($this->file) {
            $array[] = $this->project->identifier . '/' . $this->file;
        }

        if ($this->folder) {

            foreach ($disk->files($this->project->identifier . '/' . $this->folder) as $file) {
                $array[] = $file;
            }
        }

        return array_values(array_unique($array));
    }
}
