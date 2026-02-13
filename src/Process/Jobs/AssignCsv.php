<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Types\CsvImport;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Import\Csv\Importer;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignCsv implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private array $config)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $instruction = Find::instruction($project = Project::find($this->project), 'Import')
            ->find($this->config['type']);

        $this->batch()
            ->add(
                (new Importer($project, $instruction))
                    ->getItems()
                    ->chunk(25)
                    ->map(fn ($chunk) => new CsvImport($this->project, $this->config['type'], $chunk))
            );
    }

    public function tags(): array
    {
        return ['render', $this->config['type']];
    }
}
