<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class CustomerGroups extends BaseInstructions implements InstructionInterface
{
    public function get() : array
    {
        return [
            Set::UploadProcessor()
                ->process(
                    fn(Collection $items, Api $api) => $items->each(
                        function ($item) use ($api) {
                            $array = $item->toShopArray();

                            if ($item->shopware('id')) {
                                $response = $api->customerGroup()->patch(
                                    $array,
                                    $item->shopware('id')
                                );
                            } else {
                                $response = $api->customerGroup()->create($array);
                            }

                            $item->shopware(['id' => $response->body()->data->id]);
                            $item->updateOrCreate(false);
                        }
                    )
                )
        ];
    }
}
