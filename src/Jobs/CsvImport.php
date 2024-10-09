<?php

namespace Go2Flow\Ezport\Jobs;

use Go2Flow\Ezport\Finders\Find;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Go2Flow\Ezport\Import\Csv\Transformer;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Collection;

class CsvImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private string $type, private Collection $items){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        (new Transformer(
            Find::instruction(Project::find($this->project), 'Import')->find($this->type),
        ))->setItems($this->items)
            ->process();
    }
}
