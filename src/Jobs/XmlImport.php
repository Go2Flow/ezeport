<?php

namespace Go2Flow\Ezport\Jobs;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Import\Xml\DataProcessor;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class XmlImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private Collection $item, private string $key)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        (new DataProcessor(Project::find($this->project)))
            ->dataToObjects($this->item, $this->key);
    }

    public function tags()
    {
        return [
            'render',
            Find::instruction(Project::find($this->project), 'Import')->byKey($this->key)->get('type') ?? Generic::class
        ];
    }
}
