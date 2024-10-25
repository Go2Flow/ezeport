<?php

namespace Go2Flow\Ezport\Instructions\Setters\Special\ArticleProcessorSub;

use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Connectors\ShopwareSix\ShopSix;

class ArticleProcessorApiCalls {

    public function __construct(private readonly Api $api) {}

    public function create($item) {
        return $this->api->product()
            ->create($item->toShopArray());
    }

    public function patch(array $array) {
        return $this->api->product()
            ->patch($array, $array['id']);
    }

    public function deleteProducts(array $ids) {
        $this->api->product()->bulkDelete(
            $ids
        )->body();
    }

    public function bulkProducts(array $items) {
        return $this->api->product()->bulk(
            $items
        );
    }

    public function configuratorSettings(array $array, $action = 'bulk')
    {
        $response = $this->api->productConfiguratorSetting()->$action(
            $array,
        );
    }

    public function deleteProperty($items)
    {
        $this->api->productProperty()->bulkDelete(
            $items
        );
    }

    public function deleteCategory($items)
    {
        $this->api->productCategory()->bulkDelete(
            $items
        );
    }

    public function getProducts($items) : ?object {
        return collect($this->api->product()
            ->association(
                ShopSix::association([
                    'children',
                    'categories',
                    'properties',
                    'options',
                    'configuratorSettings',
                ])
            )->filter(
                ShopSix::filter([
                    'value' => $items->map->shopware('id')->toArray(),
                    'type' => 'equalsAny'
                ])
            )->search()
            ->body()?->data);
    }
}
