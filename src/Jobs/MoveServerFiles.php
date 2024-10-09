<?php

namespace Go2Flow\Ezport\Jobs;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
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
use Illuminate\Support\Stringable;

class MoveServerFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private array $array)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $instruction = Find::instruction($project = Project::find($this->project), 'api')->find('ftp');

        foreach ($this->getFiles($project) as $file)
        {
            if ($this->shouldNotMove($file)) continue;

            Find::api($project, 'ftp')->{$this->array['from']}()
                ->moveFile(
                    $instruction->get($file->beforeLast('/')) . '/' . $file->afterLast('/'),
                    $this->array['to'],
                    $this->setName($file->afterLast('/'))
                );
        }
    }

    private function getFiles(Project $project) : ?Collection
    {
        $files = collect(Storage::allFiles($project->customerStorage($this->array['from'])))
            ->map(fn ($file) => Str::of($file)->afterLast(Str::ucfirst($project->identifier) . '/'));

        if ($this->checkArray('files')) return $files;

        if ($this->checkArray('name')) return $files->filter(fn ($file) => $file->contains($this->array['name']));

        return null;
    }

    private function setName(Stringable $file) : string
    {
        if (! $this->checkArray('addTimeStamp')) return $file;

        return $file->beforeLast('.') . '_' . now()->timestamp . '.' . $file->afterlast('.');
    }

    private function shouldNotMove(Stringable $file)
    {
        if (!isset($this->array['not'])) return false;

        if (collect($this->array['not'])->contains($file->afterLast('/'))) return true;
    }

    private function checkArray(string $name) : bool{

        return isset($this->array[$name]) && $this->array[$name];
    }
}
