<?php

namespace Go2Flow\Ezport\Jobs;

use Go2Flow\Ezport\Models\GenericModel;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignRemoveModel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $this->batch()->add(
            GenericModel::whereProjectId($this->project)
                ->whereTouched(false)
                ->pluck('id')
                ->chunk(100)
                ->map(
                    fn ($chunk) => new ModifyModel(
                        $this->project,
                        ['action' => [], 'method' => 'delete'],
                        $chunk->toArray()
                    )
                )
        );
    }

    public function tags()
    {
        return ['setting up model removal'];
    }
}
