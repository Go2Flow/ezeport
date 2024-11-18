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
use Illuminate\Support\Collection;
use Saloon\XmlWrangler\XmlReader;

class XmlProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(public int $project, private Collection $item, private string $key)
    {
        //
    }

    public function handle() {

        $reader = XmlReader::fromString($this->item[0]);

            Find::instruction(
                Project::find($this->project),
                'Import'
            )->byKey($this->key)
                ->get('process')(collect($reader->values())->first());
    }
}
