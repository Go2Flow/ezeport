<?php

namespace Go2Flow\Ezport\Cleaners\ShopwareSix;

use Illuminate\Support\Collection;

class ManufacturerCleaner extends BaseCleaner
{
    protected string $type = 'manufacturer';

    public function clean()
    {
        $this->difference = $this->serverDatabaseDifference(
            $this->itemsFromShop()->pluck('id')
        );

        $this->process();
    }

    protected function process()
    {
        $this->difference = $this->productManufacturers(
            $difference = $this->difference
        )->flatten();


        $this->removeProductManufacturer();

        $this->difference = $difference;

        $this->remove();
    }

    private function productManufacturers(Collection $difference): Collection
    {
        return $this->getFromShop(
            $difference,
            [
                'associations' => ['products'],
                'include' => ['product' => ['id']],
            ],
        );
    }

    private function removeProductManufacturer(): Collection
    {
        return $this->bulkDelete(
            'productManufacturer',
            $this->prepareAssociationForDeletion(
                'products',
                fn ($option, $subOptions) => $subOptions->map(
                    fn ($subOption) => [
                        'productId' => $subOption->id,
                        'manufacturerId' => $option->id,
                    ]
                )
            )
        );
    }
}
