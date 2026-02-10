<?php

namespace Go2Flow\Ezport\Commands\Prepare;

use Go2Flow\Ezport\Connectors\ShopwareSix\Api;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Deleter
{
    const PAGECOUNT = 500;

    private Api $api;

    public function __construct(Api $api)
    {
        $this->api = $api;
    }

    public function remove($type) : void
    {
        Log::debug('Ezport: Removing ' . Str::plural($type));
        $deleteWay = (collect(['category'])->contains($type))
            ? 'single'
            : 'bulk';

        $totalNow = 0;

        $response = $this->getNextIds($type);
        $totalNow = $response->total;

        $totalThen = $totalNow + 1;

        while ($totalNow < $totalThen ) {

            Log::debug('Ezport: ' . $totalNow . ' remaining');
            $ids = $this->extractIds(
                $response
            );
            $totalThen = $totalNow;

            $this->$deleteWay($ids, $type);

            $response = $this->getNextIds($type);
            $totalNow = $response->total;

            if ($totalNow == 0) break;
        }

        $this->api->index();
    }

    private function getNextIds($type)
    {
        return $this->api
            ->$type()
            ->include([$type => ['id']])
            ->limit(self::PAGECOUNT)
            ->totalCount()
            ->search()
            ->body();
    }

    private function extractIds($response) : Collection
    {
        return collect($response->data)->map(fn ($item) => $item->id);
    }

    private function single($chunk, $type) : void
    {
        foreach ($chunk as $item) {
            $this->api->$type()->delete($item);
        }
    }

    private function bulk($chunk, $type)
    {
        return $this->api->$type()->bulkDelete(
            $chunk->map(
                fn ($item) => ['id' => $item]
            )->values()->toArray()
        );
    }
}
