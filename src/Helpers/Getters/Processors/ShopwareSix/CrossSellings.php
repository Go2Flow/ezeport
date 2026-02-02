<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;

use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class CrossSellings extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers, StandardShopSixArticle;

    public function get() : array
    {
        return [
            Set::UploadProcessor('crossSelling')
                ->process(
                    function (Collection $items, Api $api) {

                        $this->removeExistingCrossSellings(
                            $items->map(fn ($item) => $item->shopware('id'))
                                ->filter()
                                ->toArray(),
                            $api
                        );

                        $this->addCrossSellings($items, $api);

                    }
                ),
        ];
    }

    private function addCrossSellings(Collection $items, Api $api ) : void {

        $collection = $items->map(
            fn($item) => $item->setStructureByString("crossSelling")->toShopArray()
        )->filter()
            ->flatten(1)
            ->values();

        if ($collection->isEmpty()) return;

        $bulk = $api->productCrossSelling()
            ->bulk($collection->toArray())
            ->body()
            ?->data;

        if (!$bulk) {

            $items->each(fn ($item) => $item->logError([
                'reason' => 'failed to upload cross selling',
                'api-error-messages' => $api->getClient()->getErrorMessages()
            ]));

            return;
        }

        $crossSellings = collect(
            $api->productCrossSelling()
                ->association(['assignedProducts' => []])
                ->filter([
                    'type' => 'equalsAny',
                    'value' => $bulk->product_cross_selling,
                    'field' => 'id'
                ])->search()->body()->data
        );

        foreach ($items as $item) {

            $filteredCrossSellings = $crossSellings->filter(fn ($crossSelling) => $crossSelling->productId == $item->shopware('id'));

            if ($filteredCrossSellings->isEmpty()) continue;

            $item->shopware(['cross_sellings' => $filteredCrossSellings->mapWithKeys(fn ($selling) => [$selling->name => $selling->id])]);
            $item->updateOrCreate();
        }

    }

    private function removeExistingCrossSellings(array $ids, Api $api) {

        $response =$api->productCrossSelling()->filter([
            'value' => $ids,
            'type' => 'equalsAny',
            'field' => 'productId'
        ])->search()->body();


        $deletes = collect($response->data)->map(fn ($item) => ['id' => $item->id]);

        if ($deletes->isEmpty()) return;

        $api->productCrossSelling()->bulkDelete($deletes->toArray());


    }
}
