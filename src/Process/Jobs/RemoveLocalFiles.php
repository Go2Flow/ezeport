<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RemoveLocalFiles implements ShouldQueue
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
        collect(($storage = Storage::drive('public'))
            ->allFiles(Str::ucfirst(Project::find($this->project)->identifier) . '/' . $this->array['path']))
            ->each(
                fn ($file) => $storage->delete($file)
            );
    }
}
