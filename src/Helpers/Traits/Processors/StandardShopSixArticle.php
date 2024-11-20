<?php

namespace Go2Flow\Ezport\Helpers\Traits\Processors;

use Go2Flow\Ezport\Connectors\ShopwareSix\ShopSix;
use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadProcessor;
use Go2Flow\Ezport\Logger\LogError;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait StandardShopSixArticle
{

    protected function getArticleProcessor(): UploadProcessor
    {
        return Set::ArticleProcessor('Article');

    }

    private function patchArticle(Generic $item, array $array, Api $api) : void
    {
        if (!$product = $this->getShopwareProduct($array, $api)) {

            (New LogError($this->project->id))
                ->level('high')
                ->log('could not find Product ' . $item->unique_id . ' in Shopware');
        }

        foreach (['optionId' => 'property', 'categoryId'  => 'category'] as $key => $type) {

            if (($leftovers = $this->getNewItems($product, $type, $array))->count() == 0) continue;

            $api->{'product' . Str::ucfirst($type)}()->bulkDelete(
                $leftovers->map(
                    fn ($option) => [
                        'productId' => $array['id'],
                        $key => $option
                    ]
                )->values()
                    ->toArray()
            )->body();
        }

        $this->patchConfigurationSettings(
            collect($array['configuratorSettings'] ?? [])->pluck('optionId'),
            collect($product->configuratorSettings)->pluck('optionId'),
            $array['id'],
            $api
        );

        $this->patchChildren(
            $array['children'] ?? [],
            $product,
            $array['id'],
            $api
        );

        foreach ($this->unsetters() as $unsetter) unset($array[$unsetter]);

        $api->product()->patch($array, $array['id']);
    }

    private function getNewItems(object $product, string $type, array $array): Collection
    {
        if ($types = $product->{Str::plural($type)}) {
            $leftovers = collect($types)
                ->pluck('id')
                ->diff(
                    collect($array[Str::plural($type)])
                        ->pluck('id')
                );

            if ($leftovers->count() > 0) return $leftovers;
        }

        return collect();
    }

    private function patchConfigurationSettings(Collection $dbIds, Collection $shopwareIds, string $productId, Api $api)
    {
        foreach (['bulk' => $dbIds->diff($shopwareIds), 'bulkDelete' => $shopwareIds->diff($dbIds)] as $action => $ids) {

            $optionsCollection = $ids->map(
                fn ($id) => [
                    'productId' => $productId,
                    'optionId' => $id,
                ]
            )->filter(
                fn ($item) => $item['optionId'] != null && $item['productId'] != null
            )->values();

            if (($optionsCollection)->count() === 0) continue;

            $api->productConfiguratorSetting()->$action(
                $optionsCollection->toArray(),
            )->body();
        }
    }

    private function storeArticleShopwareIds(Generic $item, object $data): void
    {
        $item->shopware(['id' => $data->id]);
        $item->updateOrCreate(false);
    }

    private function patchChildren(array $children, object $product, string $id, Api $api): void
    {
        if (count($children) === 0) return;

        $serverIds = collect($product->children)->mapWithKeys(fn ($child) => [$child->id => $child->optionIds]);
        $dbOptions = collect($children)->pluck('options')->flatten();

        $deletes = $serverIds->keys()
            ->diff(
                $serverIds->map(
                    fn ($options, $productId) => collect($options)->filter(fn ($option) => $dbOptions->contains($option))->count() > 0
                        ? $productId
                        : null
                )->filter()
            )->map(fn ($item) => ['id' => $item]);

        if ($deletes->count() > 0) {
            $api->product()->bulkDelete(
                $deletes->values()->toArray()
            )->body();
        }

        $api->product()->bulk(
            collect($children)->map(
                function ($child) use ($id, $product) {
                    if ($childId = collect($product->children)->filter(fn ($variant) => $variant->productNumber == $child['productNumber'])->first()?->id) {
                        $child['id'] = $childId;
                    }
                    $child['parentId'] = $id;

                    return $child;
                }
            )->values()->toArray()
        )->body();
    }

    private function getShopwareProduct(array $array, Api $api): ?object
    {
        return collect($api->product()
            ->association(
                ShopSix::association([
                    'children',
                    'categories',
                    'properties',
                    'options',
                    'configuratorSettings',
                ])
            )->filter(
                ShopSix::filter(['value' => $array['id']])
            )->search()
            ->body()?->data)->first();
    }

    private function unsetters(): array
    {
        return [
            'children',
            'visibilities',
            'configuratorSettings',
            'cmsPageId',
            'currencyId',
            'taxId',
        ];
    }

    private function logProblem(string $problem): void
    {
        $log = new LogError($this->project->id);

        $log->type('api')->level('high')->log(json_encode($problem));

    }
}
