<?php

namespace Go2Flow\Ezport\Cleaners\ShopwareSix;

class ProductCleaner extends BaseCleaner {

    protected string $type = 'product';

    public function clean()
    {
        $this->difference = $this->serverDatabaseDifference($this->itemsFromShop()->pluck('id'));

    }

    protected function process()
    {
        $this->remove();
    }
}
