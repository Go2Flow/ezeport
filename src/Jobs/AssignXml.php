<?php

namespace Go2Flow\Ezport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Go2Flow\Ezport\Import\Xml\Split;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Str;

class AssignXml implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $project,
        private array $config,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $project = Project::find($this->project);

        collect($this->getFiles($project))
            ->each(
                fn ($path) => $this->batch()
                    ->add(
                        (new Split($path, $project))
                            ->batch($this->config['action'])
                            ->getJobs()
                    )
            );
    }

    private function getFiles($project)
    {
        $storage = Storage::drive($this->config['drive'] ?? 'public');

        return isset($this->config['files']) && $this->config['files'] === true
            ? collect($storage->files(Str::ucfirst($project->identifier) . '/' . $this->config['path']))
                ->map(fn ($file) => $storage->path($file))
            : $storage->path(Str::ucfirst($project->identifier). '/' . ($this->config['path'] ?? null));
    }
}
