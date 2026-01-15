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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModifyImportFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(public int $project, private array $config)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle()
    {

        $project = Project::find($this->project);

        $instruction = Find::instruction($project, 'Import')
            ->find($this->config['key']);


        $baseFolder = ucfirst($project->identifier) . "/";

        if (!empty($this->config['files'])) {

            $file = $this->getLatestFilteredFile($baseFolder);

            if (!$file) {
                return; // or log / fail
            }

            $newFile = $this->modify(Storage::get($file), $instruction);
            Storage::put($file, $newFile);

        }
        elseif (!empty($this->config['name'])) {

            $filePath = "public/" . $baseFolder . $this->config['path'] . '/' . $this->config['name'];

            $file = Storage::get($filePath);
            $newFile = $this->modify($file, $instruction);

            Storage::put($filePath, $newFile);
        }
    }

    private function getLatestFilteredFile(string $baseFolder): ?string
    {
        $path = "public/" . $baseFolder . $this->config['path'];

        $files = collect(Storage::files($path));

        if (!empty($this->config['not'])) {
            $files = $files->filter(function ($file) {
                foreach ($this->config['not'] as $filter) {
                    if (Str::of($file)->contains($filter)) {
                        return false;
                    }
                }
                return true;
            });
        }

        if ($files->isEmpty()) {
            return null;
        }

        return $files
            ->map(fn ($file) => [
                'file' => $file,
                'ts'   => Storage::lastModified($file),
            ])
            ->sortByDesc('ts')
            ->first()['file'];
    }


    private function modify($file, $instruction) {

        return ($instruction->get('process'))($file);
    }
}
