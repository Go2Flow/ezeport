<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UploadWithInstruction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $tries = 1;
    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $project,
        private array $config,
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $project = Project::find($this->project);

        $instruction = Find::instruction($project, 'Upload')
            ->find($this->config['key']);

        $instruction->getProcessor()
            ->run($instruction->prepareItems($this->config['items']));
    }
}