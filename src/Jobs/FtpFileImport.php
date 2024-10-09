<?php

namespace Go2Flow\Ezport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Collection;

class FtpFileImport implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $tries = 5;

    /**
     * Create a new job instance.
     */
    public function __construct(public Collection $chunk, public int $project, private array $config)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $project = Project::find($this->project);

        Find::instruction($project, 'Import')
            ->find($this->config['key'])
            ->run(
                $this->chunk,
                Find::api($project, 'ftp')
            );
    }
}
