<?php

namespace Go2Flow\Ezport\Cleaners\ShopwareSix;

use Closure;
use Go2Flow\Ezport\Connectors\ShopwareSix\Api;
use Go2Flow\Ezport\Connectors\ShopwareSix\ShopSix;
use Go2Flow\Ezport\ContentTypes\ActivityLog;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Jobs\CleanShop;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

abstract class BaseCleaner
{
    protected int $chunkSize = 25;
    protected int $deleteChunkSize = 5;
    const PAGECOUNT = 500;

    protected Collection $difference;
    protected string $type;

    private ?ActivityLog $log = null;

    public function __construct(
        protected Api $api,
        protected collection $database,
        protected array $config
    ){
        $this->typeSpecificActions();
    }

    public function prepareJobs(Project $project, string $type) : Collection
    {
        return $this->mapToJobs(
            $this->getIdsToDelete(),
            $project,
            $type
        );
    }

    public function processBatch(Collection $chunk, Project $project) : void
    {
        if ($action = $project->currentAction()) {
            $this->log = (new ActivityLog)
                ->action($action);
        }

        $this->difference = $chunk;

        $this->process();
    }

    protected function getIdsToDelete() : Collection
    {
        dump('getIdsToDelete');
        return $this->serverDatabaseDifference(
            $this->itemsFromShop()->pluck('id')
        );
    }

    protected function mapToJobs(Collection $items, Project $project, string $type) : Collection
    {

        return $items->chunk($this->chunkSize)
        ->map(
            fn ($chunk) => $chunk->count() > 0  ? new CleanShop(
                $project->id,
                $type,
                $chunk
            ) : null
        )->filter();
    }

    protected function bulkDelete(string $type, Collection $items): Collection
    {
        if ($items->count() === 0) return collect();

        $this->log
            ?->isShop()
            ->type($this->config['key'] ?? $type)
            ->properties(['ids' => $items])
            ->log('Deleting ' . $type);

        return $items->chunk($this->deleteChunkSize)
            ->map(
                fn ($chunk) => $this->api->$type()
                    ->bulkDelete(
                        $chunk->values()
                            ->toArray()
                    )->body()
            );
    }

    protected function process()
    {

    }

    protected function idGetter(int $page = 1) : ?\stdClass
    {
        return $this->api->{$this->type}()
            ->page($page)
            ->limit(self::PAGECOUNT)
            ->filter($this->setFilter())
            ->include([$this->type => ['id']])
            ->totalCount()
            ->search()
            ->body();
    }

    protected function itemsFromShop(): Collection
    {
        $response = $this->idGetter(1);
        $totalPages = ceil($response->total / self::PAGECOUNT);
        $pages = range(1, $totalPages);

        return collect($pages)->shuffle()->take(25)->flatMap(
            fn ($page) => collect($this->idGetter($page)->data)
        );
    }

    protected function remove(): Collection
    {

        return $this->bulkDelete(
            $this->type,
            $this->difference->map(
                fn ($id) => [
                    'id' => $id
                ]
            )
        );
    }

    protected function prepareAssociationForDeletion(string $type, Closure $closure) : Collection
    {

        return $this->difference->flatMap(
            fn ($item) => $closure($item, collect($item->$type))
        );
    }

    protected function serverDatabaseDifference(Collection $items) : Collection
    {
        return $items->diff($this->database);
    }

    protected function associationArray(array $associations) : array
    {
        return collect($associations)->mapWithKeys(
            fn ($association) => [$association => []]

        )->toArray();
    }

    protected function getFromShop(Collection $collection, $array) : Collection
    {
        return $collection->chunk($this->chunkSize)
            ->flatMap(
                fn ($chunk) => $this->api->{$array['url'] ?? $this->type}()
                    ->association(
                        ShopSix::association($array['associations'] ?? [])
                    )->include($array['include'] ?? [])
                    ->filter(
                        ShopSix::filter(['type' => 'equalsAny', 'value' => $chunk])
                    )->search()
                    ->body()
                    ->data
            );
    }

    protected function setFilter()
    {
        return $this->config['filter'] ?? [];
    }

    protected function typeSpecificActions() : void {}
}
