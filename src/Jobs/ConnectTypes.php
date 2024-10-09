<?php

namespace Go2Flow\Ezport\Jobs;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ConnectTypes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private string $type)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Content::type($this->type, Project::find($this->project))
            ->query()
            ->chunk(100,
                fn (Collection $chunk) => $chunk->toContentType()
                    ->each(
                        fn ($item) => $item->processRelations()
                        ->updateOrCreate(true)
                        ->setRelations()
                    )
        );
    }
}
