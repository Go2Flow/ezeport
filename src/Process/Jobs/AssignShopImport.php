<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Import\Shopware\Controller;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignShopImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private array $array)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (($assign = (new Controller(Project::find($this->project), $this->array))->assign())->count() > 0)
        {
            $this->batch()->add(
                $assign
            );
        }
    }

    public function tags()
    {
        return $this->array['type'];
    }
}
