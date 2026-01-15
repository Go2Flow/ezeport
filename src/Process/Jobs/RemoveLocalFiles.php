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

    public function __construct(public int $project, private array $config)
    {
        //
    }

    public function handle(): void
    {
        $project = Project::find($this->project);

        if (! $project) {
            return;
        }

        $storage = Storage::drive($this->config['drive'] ?? 'public');

        $dir = Str::ucfirst($project->identifier) . '/' . ($this->config['path'] ?? '');

        $files = collect($storage->allFiles($dir));

        if ($files->isEmpty()) {
            return;
        }

        $mode = $this->config['mode'] ?? 'all'; // 'all' | 'oldest'

        if ($mode === 'oldest') {
            $oldest = $files
                ->map(fn ($file) => ['file' => $file, 'ts' => $storage->lastModified($file)])
                ->sortBy('ts')
                ->pluck('file')
                ->first();

            if ($oldest) {
                $storage->delete($oldest);
            }

            return;
        }


        $files->each(fn ($file) => $storage->delete($file));
    }
}
