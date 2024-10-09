<?php

namespace Go2Flow\Ezport\Cleaners\ShopwareSix;

use Illuminate\Support\Collection;


class ProductMediaCleaner extends BaseCleaner
{
    protected string $type = 'productMedia';

    public function clean()
    {
        $this->difference = $this->serverDatabaseDifference(
            $this->itemsFromShop()
        )->chunk(500);

        $this->process();
    }

    protected function getIdsToDelete() : Collection {

        return $this->serverDatabaseDifference(
            $this->itemsFromShop()
        );
    }

    protected function serverDatabaseDifference(Collection $shopItems) : Collection
    {
        return $shopItems->map(
            function ($shopItem) {

                if ($product = $this->database->filter(fn ($item, $id) => $id === $shopItem->productId)?->first()) {

                    foreach ($product as $image ) {

                        if ($image == $shopItem->mediaId) return null;
                    }

                    return $shopItem->id;
                }
            }
        )->filter()->values();
    }

    protected function process()
    {
        $this->remove();
        // $this->difference->each(function ($id) {
        //     $this->api->productMedia()->delete($id);
        // });
    }
}
