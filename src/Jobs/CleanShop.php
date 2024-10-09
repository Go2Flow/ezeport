<?php

namespace Go2Flow\Ezport\Jobs;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class CleanShop implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private string $type, private Collection $chunk)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Find::instruction($project = Project::find($this->project), 'Clean')
            ->byKey($this->type)
            ->getCleaner()
            ->processBatch($this->chunk, $project);
    }

    public function tags()
    {
        return ['cleaning ' . $this->type  . ' in project ' . Project::find($this->project)->identifier . ' with ids .' . $this->chunk->implode(', ')];
    }
}
