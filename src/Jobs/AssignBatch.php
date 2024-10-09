<?php

namespace Go2Flow\Ezport\Jobs;

use Go2Flow\Ezport\Instructions\Setters\Schedule;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignBatch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private Schedule $schedule, private array $lock)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->schedule->setProject(Project::find($this->project))->jobs($this->lock);
    }
}
