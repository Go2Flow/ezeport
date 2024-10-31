<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class Manufacturers extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers, StandardShopSixArticle;

    public function get() : array
    {
        return [
            Set::UploadProcessor('manufacturer')
                ->process(
                    function (Collection $items, Api $api) {

                        $current = collect($api->manufacturer()->limit(500)->search()->body()->data);

                        if ($current->isEmpty()) $create = $items;
                        else {
                            $create = $items->map(
                                function ($item) use ($current) {

                                    $match = $current->filter(
                                        fn ($c) => $c->name == $item->unique_id
                                    );

                                    if ($match->isNotEmpty()) {
                                        $item->shopware(['id' => $match->first()->id]);
                                        $item->updateOrCreate();
                                        return;
                                    }
                                    return $item;
                                }
                            )->filter();
                        }

                        if ($create->isNotEmpty()) {
                            $created = $api->manufacturer()->bulk(
                                $create->values()->map->toShopArray()->toArray()
                            );

                            $this->updateWithShopwareValue(
                                $created->body()->data,
                                $create->values(),
                                ['product_manufacturer' => 'id']
                            );
                        }
                    }
                ),
        ];
    }
}
