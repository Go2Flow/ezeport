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

        if (isset ($this->config['files']) && $this->config['files']) {

            foreach ($this->getAndFilterFiles($baseFolder) as $file) {

                $newFile = $this->modify(Storage::get($file), $instruction);

                Storage::put($file, $newFile);

            }
        }
        elseif (isset($this->config['name']) && $this->config['name']) {

            $file = Storage::get("public/" . $baseFolder . $this->config['path'] . '/' . $this->config['name']);

            $newFile = $this->modify($file, $instruction);


            Storage::put("public/" . $baseFolder . $this->config['path'] . '/' . $this->config['name'], $newFile);
        }
    }

    private function getAndFilterFiles($baseFolder) {

        return collect(Storage::files("public/" . $baseFolder . $this->config['path']))
            ->when(
                isset($this->config['not']),
                function ($collection) {
                    return $collection->filter(
                        function ($file) {
                            foreach ($this->config['not'] as $filter) {

                                if (Str::of($file)->contains($filter)) return false;
                            }
                            return true;
                        }
                    );
                }
            );
    }

    private function modify($file, $instruction) {

        return ($instruction->get('process'))($file);
    }
}
