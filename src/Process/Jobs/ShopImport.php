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
use Illuminate\Support\Collection;

class ShopImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private array $config, private Collection $chunk)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        (new Controller(Project::find($this->project), $this->config))
            ->process($this->chunk);
    }

    public function tag()
    {
        return $this->config['key'] . ' ' . $this->chunk->implode(', ');
    }
}
