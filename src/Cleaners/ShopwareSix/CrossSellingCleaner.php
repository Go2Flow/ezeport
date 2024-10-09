<?php

namespace Go2Flow\Ezport\Cleaners\ShopwareSix;

class CrossSellingCleaner extends BaseCleaner
{
    protected string $type = 'productCrossSelling';
    protected int $chunkSize = 15;

    public function clean()
    {
        $this->difference = $this->serverDatabaseDifference(
            $this->itemsFromShop()->pluck('id')
        );

        $this->process();
    }

    protected function process()
    {

        $this->remove();
    }
}
