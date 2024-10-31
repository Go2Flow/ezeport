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

                        $collection = $items->map(
                            fn($item) => $item->setStructureByString("crossSelling")->toShopArray()
                        )->filter()
                            ->flatten(1)
                            ->values();

                        if ($collection->isEmpty()) return;

                        $bulk = $api->productCrossSelling()
                            ->bulk($collection->toArray())
                            ->body()
                            ->data;

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

                        $deleteAssignedProducts = $crossSellings->flatMap(
                            fn($crossSelling) => collect($crossSelling->assignedProducts)->map(
                                fn($product) => $product->id
                            )
                        )->diff($bulk->product_cross_selling_assigned_products);

                        if ($deleteAssignedProducts->isNotEmpty()) {
                            $api->productCrossSellingAssignedProducts()->bulkDelete(
                                $deleteAssignedProducts->map(fn ($product) => ['id' => $product])
                                    ->values()
                                    ->toArray()
                            );
                        }
                    }
                ),
        ];
    }
}
