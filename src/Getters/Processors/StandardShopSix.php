<?php

namespace Go2Flow\Ezport\Getters\Processors;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Helpers\Processors\GeneralHelpers;
use Go2Flow\Ezport\Instructions\Helpers\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class StandardShopSix extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers, StandardShopSixArticle;

    public function get() : array
    {
        return [
            Set::ArticleProcessor('Article'),
            Set::UploadProcessor('Customer')
                ->process(
                    function (Collection $items, Api $api) {

                        foreach ($items as $item)

                        foreach ($items->chunk(10) as $chunk){

                            $response = $api->customer()->bulk(
                                $chunk->map(
                                    fn ($item) =>
                                        $item->toShopArray()

                                )->values()
                                ->toArray()
                            )->body()?->data;

                            if ($response) {

                                $customers = collect($response->customer);

                                while($chunk->count() > 0) {
                                    $item = $chunk->shift();
                                    $item->shopware(['id' => $customers->shift()]);
                                    $item->updateOrCreate();
                                }
                            }
                        }
                    }
                ),
            Set::UploadProcessor('manufacturer')
                ->process(
                    function (Collection $items, Api $api) {

                        $current = collect($api->manufacturer()->get(500)->body()->data);

                        if ($current->isEmpty()) $create = $items;
                        else {
                            $create = $items->map(
                                function ($item) use ($current) {

                                    $match = $current->filter(
                                        fn ($c) => $c->name == $item->unique_id
                                    );

                                    if ($match->isNotEmpty()) {
                                        $item->shopware(['id' => $match->first()->id]);
                                        $item->updateOrCreate();
                                        return;
                                    }
                                    return $item;
                                }
                            )->filter();
                        }

                        if ($create->isNotEmpty()) {
                            $created = $api->manufacturer()->bulk(
                                $create->values()->map->toShopArray()->toArray()
                            );

                            $this->updateWithShopwareValue(
                                $created->body()->data,
                                $create->values(),
                                ['product_manufacturer' => 'id']
                            );
                        }
                    }
                ),

            Set::UploadProcessor('media')
                ->process(
                    function (Collection $items, Api $api) {

                        $response = $api->media()
                            ->bulk(
                                $items->toShopArray()
                            )->body();

                        for ($i = 0; $i < count($items); $i++) {
                            $items[$i]->shopware([
                                'id' => $response->data->media[$i]
                            ]);
                            $items[$i]->updateOrCreate();
                        }
                    }
                ),

            Set::UploadProcessor('propertyGroup')
                ->process(
                    function (Collection $items, Api $api, array $config) {

                        if ($items->isEmpty()) return;

                        if (!isset($config['shopware'])) {

                            $config['shopware'] = $this->getOrCreatePropertyGroup($config['group'], $api);
                        }

                        $response = $this->createOrUpdatePropertyGroupOptions(
                            $config['shopware'],
                            $items->toShopArray(),
                            $api
                        );

                        $this->setPropertyOptionId(
                            $response,
                            $items,
                            $config['stored_id_type'] ?? 'id'
                        );
                    }
                ),

            Set::UploadProcessor('crossSelling')
                ->process(
                    function (Collection $items, Api $api) {

                        foreach ($items as $item) {

                            $this->removeCrossSellingFromShop(
                                $item,
                                $api
                            );

                            if ($ids = $this->addCrossSellingToShop($item, $api)) {

                                $item->shopware([
                                    'cross_sellings' => $ids
                                ]);

                                $item->updateOrCreate();
                            }

                        }
                    }
                ),

            Set::UploadProcessor('articleMedia')
                ->process(
                    function (Collection $items, Api $api) {
                        $images = $this->getUniqueImages(
                            collect($items->toFlatShopArray()),
                            $api
                        )->filter()
                            ->values();

                        if (count($images) == 0) return;

                        $api->productMedia()
                            ->bulk($images->toArray())
                            ->body();
                    }
                ),

            Set::UploadProcessor('ImageUrl')
                ->process(
                    fn (Collection $items, Api $api) => $items->each(
                        function ($item) use ($api) {
                            $array = $item->toShopArray();
                            $api->media()
                                ->url($array['path'], $array['id']);
                        }
                    )
                ),

            Set::UploadProcessor('Category')
                ->process(
                    function (Collection $items, Api $api) {
                        foreach ($items as $category) {

                            $array = $category->toShopArray();

                            if (!isset($array['parentId'])) continue;

                            $response = $api->category()
                                ->{$category->shopware('id') ? 'patch' : 'create'}(
                                    $array,
                                    $category->shopware('id')
                                )->body();

                            if ($response) {
                                $category->shopware(['id' => $response->data->id]);
                                $category->updateOrCreate();
                            }
                        }
                    }
                ),
            Set::UploadProcessor('imageGroup')
                ->process(
                    function (Collection $items, Api $api) {

                        $ftp = Find::Api($this->project, 'ftp');
                        $content = $items->flatMap(
                            function ($item) use ($ftp) {

                                if (count($images = $item->properties('images')->pluck('name')) == 0 || !$item->shopware('images')) return collect();

                                $files = $ftp->image()
                                    ->find($images);

                                return $files->map(
                                    fn ($file, $key) => [
                                        'file' => $file,
                                        'id' => $item->shopware('images')[$key]
                                    ]
                                );
                            }
                        );

                        if ($content->count() > 0) {

                            foreach ($content as $item) {
                                $api->media()
                                    ->upload(
                                        ['file' => $item['file']],
                                        $item['id']
                                    );
                            }
                        }
                    }
                ),
            Set::UploadProcessor('Units')
                ->process(
                    fn (Collection $items, Api $api) => $items->each(
                        function ($item) use ($api) {

                            $response = $api->unit()->{$item->shopware('id') ? 'patch' : 'create'}(
                                $item->toShopArray(),
                                $item->shopware('id')
                            )->body();

                            $item->shopware(['id' => $response->data->id]);
                            $item->updateOrCreate();
                        }
                    )
                )

        ];
    }
}
