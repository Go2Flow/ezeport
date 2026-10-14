<?php

namespace Go2Flow\Ezport\Process\Jobs;

use Closure;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Helpers\FileNameFilter;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class GetFiles implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        // the listing is cached for an hour; files that arrived or were moved since must count
        if (method_exists($api, 'forceRefreshCache')) {
            $api->forceRefreshCache();
        }

        if (isset($this->array['name'])) {
            $this->tryCatch(
                fn () => $api->findAndStore(
                    $this->array['name'],
                    $this->getDestinationFolder()
                )
            );

            return;
        }

        // file names carry a sortable time stamp: 'oldest' processes them in order, 'newest' takes the latest
        if ($this->setAndTrue('oldest') || $this->setAndTrue('newest')) {

            $this->tryCatch(
                fn () => $api->findAndStore(
                    $this->pickOne($this->filterList($api)->sort()),
                    $this->getDestinationFolder()
                )
            );

            return;
        }

        if ($this->setAndTrue('files')) {

            $this->tryCatch(
                fn () => $this->filterList($api)
                    ->each(
                        fn ($file) => $api->findAndStore(
                            $file,
                            $this->getDestinationFolder()
                        )
                    )
            );
        }
    }

    private function filterList($api): Collection
    {
        return (new FileNameFilter($this->array))->filter($api->list());
    }

    private function pickOne(Collection $sorted): string
    {
        $file = $this->setAndTrue('newest') ? $sorted->last() : $sorted->first();

        if ($file === null) {
            throw new \RuntimeException('no file matches');
        }

        return $file;
    }

    private function tryCatch(Closure $method): void
    {
        try {
            $method();
        } catch (\Exception $e) {
            if ($this->setAndTrue('continue')) {
                return;
            }

            $this->fail('File not found on ftp: '.$e->getMessage());
        }
    }

    private function setAndTrue(string $key): bool
    {
        return isset($this->array[$key]) && $this->array[$key] === true;
    }

    private function getDestinationFolder(): string
    {
        return $this->array['destination'] ?? $this->array['path'] ?? '/';
    }
}
