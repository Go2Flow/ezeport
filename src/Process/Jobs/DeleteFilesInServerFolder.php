<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Carbon\Carbon;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class DeleteFilesInServerFolder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private array $config)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $project = Project::first();

        $api = Find::Api($project, 'Ftp')->get();

        $list = $api->processed()->list();

        foreach ($list as $name) {

            if ($this->shouldNotDelete($name, $api)) continue;

            $api->processed()->delete($name);
        }
    }

    private function shouldNotDelete($file, $api)
    {
        if (!isset($this->config['olderThan'])) return false;

        try {
            $modified = Carbon::createFromTimestamp(
                $api->processed()
                    ->lastModified($file)
            );
        } catch (\Exception $e) {
            $timestamp = Str::of($file)->afterLast('_')->before('.')->toString();

            if (!is_numeric($timestamp)) return false;

            try {
                $modified = Carbon::createFromTimestamp((int) $timestamp);
            } catch (\Exception $e) {
                return false;
            }
        }

        if (! $modified) return false;

        if (now()->subDays($this->config['olderThan'])->lessThan($modified)) return true;
    }
}
