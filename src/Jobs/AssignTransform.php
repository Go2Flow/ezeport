<?php

namespace Go2Flow\Ezport\Jobs;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignTransform implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, public array $config)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $instruction = Find::instruction(Project::find($this->project), 'Transform')->find($this->config['key']);

        $instruction->pluck()
            ->getJobs()
            ->each(
                fn ($process) => $this->batch()->add($process)
            );

    }
}
