<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class Customers extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers, StandardShopSixArticle;

    public function get() : array
    {
        return [
            Set::UploadProcessor('Customer')
                ->process(
                    function (Collection $items, Api $api) {

                        foreach ($items->chunk(10) as $chunk){

                            $response = $api->customer()->bulk(
                                $chunk->map(
                                    fn ($item) => $item->toShopArray()

                                )->values()
                                    ->toArray()
                            )->body()?->data;

                            if (! $response) continue;

                            $customers = collect($response->customer);

                            while($chunk->count() > 0) {
                                $item = $chunk->shift();
                                $item->shopware(['id' => $customers->shift()]);
                                $item->updateOrCreate();
                            }
                        }
                    }
                ),
        ];
    }
}
