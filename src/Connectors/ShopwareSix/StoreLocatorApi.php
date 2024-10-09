<?php

namespace Go2Flow\Ezport\Connectors\ShopwareSix;

class StoreLocatorApi extends Api {

    public function warehouse()
    {
        return $this->setPath('neti-store-pickup-warehouse');
    }

    public function storeWarehouse()
    {
        return $this->setPath('neti-store-pickup-store-warehouse');
    }

    public function store()
    {
        return $this->setPath('neti-store-locator');
    }

    public function storeStock()
    {
        return $this->setPath('neti-store-pickup-stock');
    }

    public function storeStocks()
    {
        return $this->setPath('neti-store-pickup-stocks');
    }

    public function bulkStockPost(array $payload)
    {
        return $this->bulkStockPostRequest($payload);
    }

    /** Requests */
    protected function bulkStockPostRequest(array $payload) : self
    {
        $this->response = $this->client
            ->setAlternativeHeader([
                'headers' => [
                    'Content-Type' => 'application/json' ,
                    'Accept' => 'application/json',
                ]
            ])
            ->addToPayload(
                ['payload' => $payload]
            )

            ->sendRequest(
                '_action/neti-store-pickup/update-stocks',
                'POST',
        );

        return $this;
    }
}
