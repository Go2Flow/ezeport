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
use Illuminate\Support\Str;

class MoveFilesInServerFolder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private array $config){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $project = Project::find($this->project);

        $api = Find::Api($project, 'Ftp')->get();

        foreach ($api->{$this->config['from']}()->list() as $file) {

            if ($this->shouldNotMove($file)) continue;

            $api->{$this->config['from']}()->moveFile($file, $this->config['to']);
        }
    }

    private function shouldNotMove(string $file)
    {
        if (!isset($this->config['not'])) return false;

        if (collect($this->config['not'])->contains(Str::afterLast($file, '/'))) return true;
    }
}
