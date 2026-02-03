<?php

namespace Go2Flow\Ezport\Instructions\Setters\Special\ArticleProcessorSub;

use Go2Flow\Ezport\Finders\Config;
use Illuminate\Support\Collection;

class ArticleProcessorPatch
{
    private Collection $databaseProducts;
    private Collection $shopwareProducts;
    private Collection $items;
    private ?Config $config;
    private string $id_field;

    private array $unSetters = [
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
        $this->items = collect();
        $this->databaseProducts = collect();

        $items->each(
            function ($item) {

                $array = $item->toShopArray();

                if (count($array) == 0) return;

                $this->items->push($item);
                $this->databaseProducts->push($array);
            }
        );

        return $this;
    }

    public function setIdField(string $id_field) : self
    {
        $this->id_field = $id_field;

        return $this;
    }

    public function setConfig(Config $config) : self {

        $this->config = $config;

        return $this;
    }

    public function setShopwareProducts(Collection $shopwareProducts) : self
    {
        $this->shopwareProducts = $shopwareProducts;

        return $this;
    }

    public function unSet() : self
    {
        $unSetters = $this->unSetters;

        if ($this->config->find('articles.categories.replace') === false) {
            $unSetters[] = 'categories';
        }

        $this->databaseProducts = $this->databaseProducts->map(
            fn ($data) => collect($data)->filter(
                fn ($item, $key) => !in_array($key, $unSetters)
            )
        );

        return $this;
    }

    public function properties() : self {

        if(($leftovers = $this->prepareLeftovers('properties', 'optionId'))->count() > 0) {

            $response = $this->apiCalls->deleteProperty(
                $leftovers->toArray()
            );


            $this->logErrorToProducts($response->body(),'properties failed to be deleted on shopware');

        }

        return $this;
    }

    public function options() : self {

        if(($leftovers = $this->prepareLeftovers('options', 'optionId'))->count() > 0) {

            $response = $this->apiCalls->deleteProperty(
                $leftovers->toArray()
            );

            $this->logErrorToProducts($response->body(), 'options failed to be deleted on shopware');

        }

        return $this;
    }

    public function removePrices() : self
    {
        if (! $this->config->find('articles.prices.delete')) return $this;

        $prices = collect();

        foreach ($this->shopwareProducts as $shopwareProduct) {

            $prices = $prices->merge(
                $this->collectPrices(
                    $shopwareProduct,
                    $this->getCorrectData($shopwareProduct->id)
                )
            );
        }

        $priceIds = $prices->map(fn($item) => ["id" => $item->id])->values();

        if ($priceIds->count() > 0) {
            $response = $this->apiCalls->deletePrices($priceIds->toArray());

            $this->logErrorToProducts($response->body(), 'prices failed to be deleted on shopware');

        }

        return $this;
    }

    private function collectPrices($shopProduct, $databaseProduct) {

        $prices = collect($shopProduct->prices);

        if (! empty($databaseProduct['children'])) {

            foreach ($shopProduct->children as $child) {

                $prices = $prices->merge(collect($child->prices));
            }
        }

        return $prices;
    }

    public function categories() : self {

        if ($this->config->find('articles.categories.replace') === false) return $this;

        $items = $this->shopwareProducts->map(
            fn($product) => [
                "productId" => $product->id,
                "ids" => collect($product->categoryIds)->diff(
                    collect($this->getCorrectData($product->id)['categories'] ?? [])->flatten())
            ]
        );

        $leftOvers = [];

        foreach ($items as $item) {

            foreach ($item['ids'] ?? [] as $id) {

                $leftOvers[] = [
                    'productId' => $item['productId'],
                    'categoryId' => $id
                ];
            }
        }

        if (count($leftOvers) > 0) {
            $response = $this->apiCalls->deleteCategory(
                $leftOvers
            );

            $this->logErrorToProducts($response->body(),'categories failed to be deleted on shopware');
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

        if ($add->count() > 0) {
            $response = $this->apiCalls->configuratorSettings($add->toArray());

            $this->logErrorToProducts($response->body(), 'configurator settings failed to be added on shopware');

        }
        if ($delete->count() > 0) {
            $response = $this->prepareConfiguratorSettingsDelete($delete);

            $this->logErrorToProducts($response->body(), 'configurator settings failed to be deleted on shopware');

        }

        return $this;
    }

    private function prepareConfiguratorSettingsDelete(Collection $delete)
    {

        $response = $delete->flatMap(
            function ($item) {
                $product = $this->shopwareProducts->filter(fn ($product) => $product->id == $item['productId'])->first();

                return collect($product->configuratorSettings)->filter(fn ($setting) => $setting->optionId == $item['optionId'])->map(
                    fn ($item) => ['id' => $item->id]
                );
            }
        )->filter()->values();

        if ($response->count() > 0) {
            return $this->apiCalls->configuratorSettings($response->toArray(), 'bulkDelete');
        }

        return null;
    }

    public function children(): self
    {
        $deletes = collect();
        $children = collect();

        foreach ($this->databaseProducts as $databaseProduct) {

            $product = collect($this->getCorrectProduct($databaseProduct['id']));
            $subChildren = collect($databaseProduct['children'] ?? []);

            $children = $children->merge(
                $subChildren->map(
                    function ($child) use ($databaseProduct, $product) {

                        $child['parentId'] = $databaseProduct['id'];

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
            $response = $this->apiCalls->deleteProducts($deletes->values()->toArray());

            $this->logErrorToProducts($response->body(),'children failed to be deleted on shopware');;

        }
        if ($children->count() > 0) {

            $response = $this->apiCalls->bulkProducts($children->values()->toArray());

            if ($response = $response->body()) {
                $parents = $this->items->mapWithKeys(fn ($item) => [$item->shop($this->id_field) => collect()]);

                foreach ($response->data->product as $key => $product) {

                    $parents[$children[$key]['parentId']][$children[$key]['productNumber']] =  $product;
                }

                foreach ($this->items as $item)  {

                    if ($parents[$item->shop($this->id_field)]->count() > 0 ) {

                        $item->shop(['children' => $parents[$item->shop($this->id_field)]]);
                        $item->updateOrCreate();
                    }
                }
            }

            else {
                $this->logErrorToProducts($response, 'children failed to be created on shopware');
            }
        }

        return $this;
    }

    public function articles() : self {
        $response = $this->apiCalls->bulkProducts($this->databaseProducts->toArray());

        $this->logErrorToProducts($response->body(), 'product failed to be updated on shopware');

        return $this;
    }

    private function getConfigurationIds() : Collection
    {
        $configurationIds = collect();
        foreach ($this->shopwareProducts as $product) {
            $configurationIds->push([
                $product->id,
                collect($this->getCorrectData($product->id)['configuratorSettings'] ?? [])->pluck('optionId'),
                collect($product->configuratorSettings)->pluck('optionId')
            ]);
        }

        return $configurationIds;
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
        return $this->shopwareProducts->map(
            fn ($product) => [ 'productId' => $product->id,
                'ids' => collect($product->$type)
                    ->pluck('id')
                    ->diff(
                        collect($this->getCorrectData($product->id)[$type] ?? [])->pluck('id')
                    )
            ]);
    }

    private function getCorrectData(string $id) {

        return $this->databaseProducts->filter(fn ($item) => $item['id'] == $id)->first();
    }

    private function getCorrectProduct(string $id) {

        return $this->shopwareProducts->filter(fn ($product) => $product->id == $id)->first();
    }

    private function findChildrenThatShouldBeDeleted(Collection $productChildren, Collection $children) : Collection
    {
        if (! $this->config->find('articles.children.delete') ?? true) return collect();

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

    private function logErrorToProducts($response, string $message) : void
    {
        if ($response) return;

        $this->databaseProducts->each(fn ($item) => $item->logError([
            'reason' => $message,
            'api-error-messages' => $this->apiCalls->getErrorMessages()
        ]));
    }
}
