<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Go2Flow\Ezport\Models\GenericModel;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ModifyModel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $timeout = 210;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $project,
        private array $instructions,
        private array $items
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        GenericModel::whereIn('id', $this->items)
            ->where('project_id', $this->project)
            ->get()
            ->each
            ->{$this->instructions['method']}($this->instructions['action'] ?? null);
    }
}
