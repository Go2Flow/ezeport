<?php

namespace Go2Flow\Ezport\Helpers\Traits\Uploads;

use Closure;
use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadField;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait FieldHelpers
{
    protected function categoryParentId(Generic $item)
    {
        if (! ($categories = $item->relations('categories'))) {
            return $item->project()->cache('category_ids')['parent'];
        }

        if ($categories->first()->shopware('id')) {
            return $categories->first()->shopware('id');
        }
        if ($id = $categories->first()->refresh()->shopware('id')) {
            return $id;
        }
    }

    protected function calculatePriceWithDiscount(float|string $full, float|string|null $discount, ?string $addOrRemove = 'add') : array
    {
        return $this->checkDiscount($discount)
            ? [array_merge($this->formatPrice($discount, $addOrRemove), ['listPrice' => $this->formatPrice($full, $addOrRemove)])]
            : [$this->formatPrice($full, $addOrRemove)];
    }

    protected function getCollectionFromRelation(?Collection $items, Closure $closure): Collection
    {
        if (!$items) return collect();

        return $items->map(
            function ($item) use ($closure) {

                if (!$item->shopware('id')) return null;

                return $closure($item);
            }
        )->filter()
            ->values();
    }

    protected function addTax($price, $tax): float
    {
        return $this->applyTax($price, $tax, 'add');
    }

    protected function removeTax($price, $tax): float
    {
        return $this->applyTax($price, $tax, 'remove');
    }

    private function applyTax($price, $tax, $addOrRemove = 'add'): float
    {
        return $addOrRemove == 'add'
            ? $price * (1 + ($tax / 100))
            : $price / (1 + ($tax / 100));
    }

    protected function getFromProject($field, $cache, $key): UploadField
    {
        return Set::UploadField($field)
            ->field(
                fn ($item) => $item->project()->cache($cache)[$key]
            );
    }

    protected function setShopwareIds($item, $fields = ['id' => 'id']) {

        foreach ($fields as $from => $to){

            $array[$from]  = $item->shopware($from);
        }
    }

    protected function formatPrice(string|float $price, $addOrRemove = 'add') : array
    {
        $amount = (float) Str::replace(',', '.', $price);

        $priceObject = [
            'currencyId' => $this->project->cache('currency_ids')['standard'],
            'linked' => false,
        ];

        if ($addOrRemove == 'add') {
            $priceObject['net'] = $this->twoDecimalPlaces($amount);
            $priceObject['gross'] = $this->twoDecimalPlaces($this->addTax($amount, $this->project->settings('taxes')['standard']));
        } else {
            $priceObject['net'] = $this->twoDecimalPlaces($this->removeTax($amount, $this->project->settings('taxes')['standard']));
            $priceObject['gross'] = $this->twoDecimalPlaces($amount);
        }

        return $priceObject;
    }

    protected function getStock($item, $index) : int
    {
        return $item->properties('stocks')
            ->filter(fn ($stock) => $stock['size_index'] == $index)
            ->first()['amount'] ?? 0;
    }

    private function twoDecimalPlaces(float $amount) : float
    {
        return (float) number_format($amount, 2, '.', '');
    }
}
