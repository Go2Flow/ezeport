<?php

namespace Go2Flow\Ezport\Jobs;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
use Closure;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public $timeout = 120;
    public $tries = 10;

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
        $api = Find::api(Project::find($this->project), 'ftp')->{$this->array['path']}();

        if (isset($this->array['name']))
        {
            $this->tryCatch(
                fn() => $api->findAndStore(
                    $this->array['name'],
                    $this->array['path']
                )
            );

            return;
        }

        if (isset($this->array['not'])) $api =  $api->removeFromList($this->array['not']);

        if ($this->setAndTrue('newest')) {

            $this->tryCatch(
                fn() => $api->{$this->array['path']}()->findAndStore(
                    $api->{$this->array['path']}()->list()->first(),
                    $this->array['path']
                )
            );
            return;
        }
        if ($this->setAndTrue('files')) {

            $this->tryCatch(
            fn() => $api->{$this->array['path']}()->list()
                    ->each(
                        fn($file) => $api->{$this->array['path']}()->findAndStore($file, $this->array['path'])
                    )
            );

            return;
        }
    }

    private function tryCatch(Closure $method): void
    {
        try {
            $method();
        }
        catch (\Exception $e) {
            if ($this->setAndTrue('continue')) return;

            $this->fail('File not found on ftp');
        }
    }

    private function setAndTrue(string $key): bool
    {
        return isset($this->array[$key]) && $this->array[$key] == true;
    }
}
