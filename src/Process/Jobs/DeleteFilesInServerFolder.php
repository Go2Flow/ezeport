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
        $project = Project::find($this->project);

        $api = Find::api($project,  $this->config['api'] ?? 'ftp')->get();

        $list = $api->processed()->list();

        foreach ($list as $name) {

            if ($this->shouldNotDelete($name, $api)) continue;

            $api->processed()->delete($name);
        }
    }

    private function shouldNotDelete($file, $api)
    {
        if (!isset($this->config['olderThan'])) return false;

        // Prefer the timestamp embedded in the filename (files moved here with
        // addTimeStamp are named "..._<unixtime>.ext"). This avoids a per-file
        // lastModified() round-trip to the ftp server, which gets slow and flaky
        // once the folder holds more than a handful of files. Fall back to the
        // server's modified time only when the name carries no timestamp.
        $modified = $this->modifiedFromName($file) ?? $this->modifiedFromServer($file, $api);

        if (! $modified) return false;

        return now()->subDays($this->config['olderThan'])->lessThan($modified);
    }

    private function modifiedFromName($file) : ?Carbon
    {
        $timestamp = Str::of($file)->afterLast('_')->before('.')->toString();

        if (!is_numeric($timestamp)) return null;

        try {
            return Carbon::createFromTimestamp((int) $timestamp);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function modifiedFromServer($file, $api) : ?Carbon
    {
        try {
            return Carbon::createFromTimestamp(
                $api->processed()->lastModified($file)
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}
