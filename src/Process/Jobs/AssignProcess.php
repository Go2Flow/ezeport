<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AssignProcess implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 1;
    public int $timeout = 890;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private array $config) {}

    public function handle() : void
    {

        $instruction = Find::instruction(
            Project::find($this->project),
            'Import'
        )->find($this->config['key']);

        $this->batch()->add($instruction->getJobs($this->project));
    }

    public function tags()
    {
        return ['Import job for ' . $this->config['key']];
    }
}
