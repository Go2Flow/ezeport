<?php

namespace Go2Flow\Ezport\Instructions\Setters\Special\ArticleProcessorSub;

use Go2Flow\Ezport\Finders\Config;
use Illuminate\Support\Collection;

class ArticleProcessorPatch
{
    private Collection $data;
    private Collection $products;
    private Collection $items;

    private ?Config $config;
    private array $unsetters = [
        'children',
        'visibilities',
        'configuratorSettings',
        'cmsPageId',
        'currencyId',
        'taxId',
    ];

    public function __construct(private readonly ArticleProcessorApiCalls $apiCalls) {}

    public function setItems(Collection $items) : self
    {
        $this->items = $items;
        $this->data = collect($items->toShopArray());

        return $this;
    }

    public function setConfig(Config $config) : self {

        $this->config = $config;

        return $this;
    }

    public function setProducts(Collection $products) : self
    {
        $this->products = $products;

        return $this;
    }

    public function unSet() : self
    {
        $this->data = $this->data->map(
            fn ($data) => collect($data)->filter(
                fn ($item, $key) => !in_array($key, $this->unsetters)
            )
        );

        return $this;
    }

    public function options() : self {

        if(($leftovers = $this->prepareLeftovers('options', 'optionId'))->count() > 0) {

            $this->apiCalls->deleteProperty(
                $leftovers->toArray()

            );
        }

        return $this;
    }

    public function removePrices() : self
    {
        if ($this->config['articles']['prices']['delete'] ?? true) return $this;

        $prices = collect($this->products)
            ->map(
                fn($product) => collect($product->prices)
                    ->pluck('id')
                    ->map(fn($item) => ["id" => $item])
            );

        if ($prices->count() > 0) $this->apiCalls->deletePrices($prices->toArray());

        return $this;
    }

    public function categories() : self {

        if (($leftovers = $this->prepareLeftovers('categories', 'categoryId'))->count() > 0) {
            $this->apiCalls->deleteCategory(
                $leftovers->toArray()
            );
        }

        return $this;
    }

    public function configurationSettings() : self
    {
        $add = collect();
        $delete = collect();

        foreach ($this->getConfigurationIds() as [$productId, $dbIds, $shopwareIds]) {

            $add = $add->merge($this->prepareConfiguratorSettingsOptions($dbIds->diff($shopwareIds), $productId));
            $delete = $delete->merge($this->prepareConfiguratorSettingsOptions($shopwareIds->diff($dbIds), $productId));
        }

        if ($add->count() > 0) $this->apiCalls->configuratorSettings($add->toArray());
        if ($delete->count() > 0) $this->prepareConfiguratorSettingsDelete($delete);

        return $this;
    }

    private function prepareConfiguratorSettingsDelete(Collection $delete) : void
    {

        $response = $delete->flatMap(
            function ($item) {
                $product = $this->products->filter(fn ($product) => $product->id == $item['productId'])->first();

                return collect($product->configuratorSettings)->filter(fn ($setting) => $setting->optionId == $item['optionId'])->map(
                    fn ($item) => ['id' => $item->id]
                );
            }
        )->filter()->values();

        if ($response->count() > 0) {
            $this->apiCalls->configuratorSettings($response->toArray(), 'bulkDelete');
        }
    }

    public function children(): self
    {
        $deletes = collect();
        $children = collect();

        foreach ($this->data as $data) {
            $product = collect($this->getCorrectProduct($data['id']));
            $subChildren = collect($data['children'] ?? []);

            $children = $children->merge(
                $subChildren->map(
                    function ($child) use ($data, $product) {

                        $child['parentId'] = $data['id'];

                        if (!isset($child['id'])) {
                            $id = collect($product['children'])->filter(
                                fn ($variant) => $variant->productNumber == $child['productNumber']
                            )->first()?->id;

                            if ($id) $child['id'] = $id;
                        }

                        return $child;
                    }
                )
            );

            $deletes = $deletes->merge(
                $this->findChildrenThatShouldBeDeleted(
                    collect($product['children']),
                    $subChildren
                )
            );
        }

        if ($deletes->count() > 0) {
            $this->apiCalls->deleteProducts($deletes->values()->toArray());
        }
        if ($children->count() > 0) {

            $response = $this->apiCalls->bulkProducts($children->values()->toArray())?->body();

            if ($response) {
                $parents = $this->items->mapWithKeys(fn ($item) => [$item->shop('id') => collect()]);

                foreach ($response->data->product as $key => $product) {

                    $parents[$children[$key]['parentId']][$children[$key]['productNumber']] =  $product;
                }

                foreach ($this->items as $item) {

                    if ($parents[$item->shop('id')]->count() > 0 ) {

                        $item->shop(['children' => $parents[$item->shop('id')]]);
                        $item->updateOrCreate();
                    }
                }
            }
        }

        return $this;
    }

    public function articles() : self {
        $response = $this->apiCalls->bulkProducts($this->data->toArray());

        return $this;
    }

    private function getConfigurationIds() : Collection
    {
        $response = collect();
        foreach ($this->products as $product) {
            $response->push([
                $product->id,
                collect($this->getCorrectData($product->id)['configuratorSettings'] ?? [])->pluck('optionId'),
                collect($product->configuratorSettings)->pluck('optionId')
            ]);
        }


        return $response;
    }

    private function prepareLeftovers($type, string $key) : Collection {

        $leftovers = collect();

        foreach ($this->getRemovals($type) as $removal) {
            if ($removal['ids']->count() == 0) continue;

            foreach ($removal['ids'] as $id) {

                $leftovers->push([
                    'productId' => $removal['productId'],
                    $key => $id
                ]);
            }
        }

        return $leftovers;
    }

    private function prepareConfiguratorSettingsOptions(Collection $ids, string $productId) : Collection
    {
        return $ids->map(
            fn ($id) => [
                'productId' => $productId,
                'optionId' => $id,
            ]
        )->filter(
            fn ($item) => $item['optionId'] != null && $item['productId'] != null
        )->values();
    }

    private function getRemovals(string $type): Collection
    {
        return $this->products->map(
            fn ($product) => [ 'productId' => $product->id,
                'ids' => collect($product->$type)
                    ->pluck('id')
                    ->diff(
                        collect($this->getCorrectData($product->id)[$type] ?? [])->pluck('id')
                    )
            ]);
    }

    private function getCorrectData(string $id) {

        return $this->data->filter(fn ($item) => $item['id'] == $id)->first();
    }

    private function getCorrectProduct(string $id) {

        return $this->products->filter(fn ($product) => $product->id == $id)->first();
    }

    private function findChildrenThatShouldBeDeleted(Collection $productChildren, Collection $children) : Collection
    {
        if (! $this->config['articles']['children']['delete'] ?? true) return collect();

        $serverIds = $productChildren->mapWithKeys(fn ($child) => [$child->id => $child->optionIds]);
        $dbOptions = $children->pluck('options')->flatten();

        return $serverIds->keys()
            ->diff(
                $serverIds->map(
                    fn ($options, $productId) => collect($options)->filter(fn ($option) => $dbOptions->contains($option))->count() > 0
                        ? $productId
                        : null
                )->filter()
            )->map(fn ($item) => ['id' => $item]);
    }
}
