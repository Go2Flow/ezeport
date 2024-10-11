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
use Illuminate\Support\Collection;

class Transform implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $tries = 1;
    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $project,
        private string $type,
        private Collection|null $chunk,
        private array|Collection $config = [])
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Find::instruction(Project::find($this->project), 'Transform')
            ->byKey($this->type)
            ?->run($this->chunk, $this->config);
    }

    public function tags(): array
    {
        return ['process', $this->type . ' ' .  $this->chunk?->implode(', ')];
    }
}
