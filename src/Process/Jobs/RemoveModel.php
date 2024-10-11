<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Go2Flow\Ezport\Models\GenericModel;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class RemoveModel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private array|Collection $items)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        GenericModel::where('project_id', $this->project)
            ->whereIn('id', $this->items)
            ->get()
            ->each
            ->delete();
    }

    public function tags()
    {
        return ['deleting-models ' . $this->items->implode(', ')];

    }
}
