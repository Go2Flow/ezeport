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
        return $this->api->product()->bulkDelete(
            $ids
        );
    }

    public function bulkProducts(array $items) {
        return $this->api->product()->bulk(
            $items
        );
    }

    public function configuratorSettings(array $array, $action = 'bulk')
    {
        return $this->api->productConfiguratorSetting()->$action(
            $array,
        );
    }

    public function deleteProperty($items)
    {
        return $this->api->productProperty()->bulkDelete(
            $items
        );
    }

    public function deleteCategory(array $items)
    {
        return $this->api->productCategory()->bulkDelete(
            $items
        );
    }

    public function deletePrices(array $prices) {

        return $this->api->productPrice()->bulkDelete($prices);
    }

    public function getErrorMessages() : array
    {
        return $this->api->getClient()->getErrorMessages();
    }

    public function getProducts(array $ids) : ?object {
        return collect($this->api->product()
            ->association([
                "children" => [
                    "associations" => [
                        "prices" => []
                    ]
                ],
                "properties" => [],
                "options" => [],
                "configuratorSettings" => [],
                "prices" => []
            ])->filter(
                ShopSix::filter([
                    'value' => $ids,
                    'type' => 'equalsAny'
                ])
            )->search()
            ->body()?->data);
    }
}
