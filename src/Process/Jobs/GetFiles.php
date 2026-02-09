<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Closure;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        $api = Find::api(Project::find($this->project), $this->array['api'] ?? 'ftp');

        if (isset($this->array['path'])) {
            $api = $api->{$this->array['path']}();
        }

        if (isset($this->array['name']))
        {
            $this->tryCatch(
                fn() => $api->findAndStore(
                    $this->array['name'],
                    $this->array['path'] ?? '/'
                )
            );

            return;
        }

        if ($this->setAndTrue('newest')) {

            $this->tryCatch(
                fn() => $api->findAndStore(
                    $this->filterList($api)->first(),
                    $this->array['path'] ?? '/'
                )
            );
            return;
        }
        if ($this->setAndTrue('files')) {

            $this->tryCatch(
                fn() => $this->filterList($api)
                    ->each(
                        fn($file) => $api->findAndStore($file, $this->array['path'] ?? '/')
                    )
            );
        }
    }

    private function filterList($api) : Collection
    {
        $list = $api->list();

        if (isset($this->array['not'])) {

            $list = $list->filter(fn ($file) => $file !== $this->array['not']);
        }

        if (isset($this->array['type'])) {

            $list = $list->filter(fn ($file) => Str::endsWith($file, $this->array['type']));
        }

        return $list;
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
        return isset($this->array[$key]) && $this->array[$key] === true;
    }
}
