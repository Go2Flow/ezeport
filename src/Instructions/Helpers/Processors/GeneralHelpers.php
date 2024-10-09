<?php

namespace Go2Flow\Ezport\Instructions\Helpers\Processors;

use Go2Flow\Ezport\Connectors\ApiInterface;
use Go2Flow\Ezport\Connectors\ShopwareSix\ShopSix;
use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Finders\Api;
use Illuminate\Support\Collection;

trait GeneralHelpers
{

    protected function updateWithShopwareValue($responseArray, Collection $collection, array $values): void
    {
        for ($i = 0; $i < $collection->count(); $i++) {
            $model = $collection->get($i);

            $update = false;
            foreach ($values as $key => $field) {
                $item = isset($responseArray->$key[$i]) ? $responseArray->$key[$i] : $responseArray->$key[0];
                if ($model && $model->shopware($field) !== $item) {

                    $model->shopware([$field => $item]);
                    $update = true;
                }
            }
            if ($update) $model->updateOrCreate(false);
        }
    }

    protected function createOrUpdatePropertyOptions($data, array $options, Api $api) : ApiInterface
    {
        for ($i = 0; $i < count($options); $i++) {
            $options[$i]['groupId'] = $data['id'];
        }

        return $api->propertyOption()->bulk(
            $options
        );
    }

    protected function getOrCreatePropertyGroup(string $name, Api $api): array
    {
        $response = $api
            ->propertyGroup()
            ->association(ShopSix::association(['options']))
            ->filter(
                ShopSix::filter(['value' => $name, 'field' => 'name'])
            )->search()
            ->body();

        return ($response->total == 0)
            ? (array) $api->propertyGroup()
                ->create([
                    'name' => $name,
                    'displaytype' => 'text',
                    'sortingType' => 'alphanumeric',
                ])->body()->data
            : (array) $response->data[0];
    }

    protected function createOrUpdatePropertyGroupOptions($data, array $options, Api $api) : ApiInterface
    {
        for ($i = 0; $i < count($options); $i++) {
            $options[$i]['groupId'] = $data['id'];
        }

        return $api->propertyOption()->bulk(
            $options
        );
    }

    protected function setPropertyOptionId($response, $options, $optionId = 'id'): void
    {
        if ($response) {
            $this->updateWithShopwareValue(
                $response->body()?->data,
                $options,
                [
                    'property_group' => 'group_id',
                    'property_group_option' => $optionId,
                ]
            );
        }
    }

    protected function removeCrossSellingFromShop($item, Api $api): void
    {

        $ids = $this->prepareCrossSellingIdsForDeletion(
            $api->productCrossSelling()
                ->association(
                    ShopSix::association(['assignedProducts'])
                )->filter(
                    ShopSix::filter([
                        'field' => 'productId',
                        'value' => $item->shopware("id")
                    ])
                )->search()
                ->body()
                ->data
        );

        if ($ids->count() == 0) return;

        $api->productCrossSellingAssignedProducts()
            ->bulkDelete(
                $ids->map(fn ($item) => ['id' => $item])->toArray()
            );
    }

    protected function addCrossSellingToShop(Generic $item, Api $api): ?Collection
    {
        return $this->prepareCrossSelling($item)
            ->filter(fn ($item) => $item['productId'] !== null)
            ->map(
                function ($crossSelling, $key) use ($api) {

                    if (isset($crossSelling['id'])) {
                        $id = $crossSelling['id'];
                        unset($crossSelling['id']);
                    } else {
                        $id = null;
                    }

                    $body = $api->productCrossSelling()
                        ->{isset($crossSelling['id']) ? 'patch' : 'create'}(
                            $crossSelling,
                            $id
                        )->body();

                    if ($body) return [
                        'id' => $body->data->id,
                        'key' => $key,
                    ];
                }
            )->filter()
            ->mapWithKeys(
                fn ($item) => [$item['key'] => $item['id']]
            );
    }

    private function prepareCrossSelling(Generic $item): Collection
    {

        $shopArray = $item->toShopArray();

        return collect(
            isset($shopArray['productId'])
                ? [$shopArray]
                : $shopArray
        );
    }

    protected function getUniqueImages(Collection $images, Api $api) : Collection
    {
        if (($mediaId = $images->pluck('mediaId'))->count() == 0) return collect();

        $response = $this->getShopwareMediaIds($api, $mediaId);

        return $images->map(
            fn ($image) => $this->getShopImage($response, $image)
        )->filter();
    }

    protected function mediaFolders(string $name, Api $api) : string
    {

        $folder = collect($api->mediaFolder()->get(30)->body()->data)
            ->filter(fn ($folder) => $folder->name === $name)->first();

        if ($folder) return $folder->id;

        $configuration = $api->mediaFolderConfiguration()
            ->get()
            ->body()->data;

        $response = $api->mediaFolder()
            ->create([
                'configurationId' => $configuration[0]->id,
                'name' => $name
            ]);

        return $response->body()->data[0]->id;
    }

    private function getShopImage(object $response, array $image) : ?array
    {
        $shopImage = $response->filter(fn ($media) => $media->id == $image['mediaId'])->first();
        return $shopImage && ! collect($shopImage->productMedia)->pluck('productId')->contains($image['productId'])
            ? $image
            : null;
    }

    private function getShopwareMediaIds(Api $api, Collection $mediaIds) : Collection
    {
        return  collect($api->media()
            ->association(['productMedia' => []])
            ->filter(
                ShopSix::filter(['type' => 'equalsAny', 'value' => $mediaIds->toArray()])
            )->search()
            ->body()->data);
    }

    private function prepareCrossSellingIdsForDeletion(array|Collection $responses) : Collection
    {
        return collect($responses)->map(
            fn ($response) => collect($response->assignedProducts)
                ->map(
                    fn ($item) => $item->id
                )
        )->flatten();
    }

    private function checkIfShopHasImages(Api $api) : bool
    {
        $response = $api->productMedia()->get()->body();
        return isset($response->total) && $response->total > 0
            ? false
            : true;
    }
}
