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

class AssignClean implements ShouldQueue
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
        $this->batch()->add(
            Find::instruction($project = Project::find($this->project), 'Clean')
                ->byKey($this->config['type'])
                ->prepareItems()
                ->getCleaner()
                ->prepareJobs($project, $this->config['type'])
        );
    }

    public function tags()
    {
        return ['setting up cleaner for ' . $this->config['type'] . ' in project ' . Project::find($this->project)->identifier . ''];
    }
}
