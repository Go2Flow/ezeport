<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Instructions\Setters\Interfaces\Assignable;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\Executable;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Import\Csv\Imports\Import;
use Go2Flow\Ezport\Process\Jobs\AssignInstruction;
use Go2Flow\Ezport\Process\Jobs\ProcessInstruction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CsvImport extends Basic implements Assignable, Executable
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
            ->class(AssignInstruction::class);
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

    public function assignJobs(): Collection
    {
        $importer = new Import($this->config);
        $disk = Storage::disk('public');

        return collect($this->fileAndFolder($disk))
            ->flatMap(fn (string $file) => $this->createChunkedJobs($importer, $disk, $file))
            ->values();
    }

    public function execute(array $config): void
    {
        ($this->process)(
            $config['items'] ?? collect([]),
            $this->has('api') ? $this->get('api')($this->project) : null,
        );
    }

    private function createChunkedJobs($importer, $disk, $file): Collection
    {
        return $importer->collection(
            $importer->toCollection(
                $disk->path($file)
            )
        )->chunk($this->chunk)
            ->map(
                fn ($chunk) => new ProcessInstruction(
                    $this->project->id,
                    array_merge(
                        ['items' => $chunk, 'instructionType' => $this->instructionType, 'key' => $this->key],
                        $this->jobConfig,
                    )
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
