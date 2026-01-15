<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Import\Xml\Split;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
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
                            ->getCollection()
                            ->flatMap(
                                function ($item, $key) {
                                    return $item->map(
                                        fn ($xml) => new ($this->config['xmlJob'] ?? XmlProcess::class)($this->project, $xml, $key)
                                    );
                                }
                            )
                    )
            );
    }

    private function getFiles(Project $project): Collection
    {
        $storage = Storage::drive($this->config['drive'] ?? 'public');

        $base = Str::ucfirst($project->identifier) . '/';
        $dir  = $base . ($this->config['path'] ?? '');

        if (!empty($this->config['files'])) {

            $files = collect($storage->files($dir));

            $latest = $files
                ->map(fn ($file) => ['file' => $file, 'ts' => $storage->lastModified($file)])
                ->sortByDesc('ts')
                ->pluck('file')
                ->first();

            return $latest
                ? collect([$storage->path($latest)])
                : collect();
        }

        return collect([$storage->path($dir)]);
    }


}
