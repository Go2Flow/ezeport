<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\ShopwareFive;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class Products extends BaseInstructions implements InstructionInterface {

    public function get() : array
    {
        return [
            Set::ShopImport('Products')
                ->type('Article')
                ->uniqueId('id')
                ->job(
                    Set::Job()
                        ->config([
                            'type' => 'products'
                        ])
                )->api(Get::api('shopFive'))
                ->items(
                    fn (Api $api): Collection => collect($api->product()->get()->body()->data)->pluck('id')
                )->process(
                    fn ($chunk, $api): Collection => $chunk->map(
                        fn ($id) => $api->product()->find($id)->body()->data
                    )
                )->properties(
                    fn ($product) => [
                        'articleNumber' => $product->mainDetail->number ?? null,

                        'name' => $product->name ?? null,
                        'description' => $product->description ?? null,
                        'descriptionLong' => $product->descriptionLong ?? null,
                        'active' => $product->active ?? null,
                        'ean' => $product->mainDetail->ean ?? null,
                        'stocks' => $product->mainDetail->inStock ?? 0,
                        'prices' => collect($product->mainDetail->prices)->map(
                            fn ($price) => [
                                'price' => $price->price,
                                'articleDetailsId' => $price->articleDetailsId
                            ]
                        ),
                        'dimensions' => [
                            'width' => $product->mainDetail->width ?? null,
                            'height' => $product->mainDetail->height ?? null,
                            'length' => $product->mainDetail->len ?? null,
                            'weight' => $product->mainDetail->weight ?? null,
                        ],

                        'supplier' => [
                            'name' => $product->supplier->name ?? null,
                            'description' => $product->supplier->description ?? null,
                            'id' => $product->supplier->id ?? null,
                        ],

                        'properties' => collect($product->propertyValues)->map(
                            fn ($property) => [
                                'text' => $property->value ?? null,
                                'position' => $property->position ?? null,
                                'optionId' => $property->optionId ?? null,
                                'mediaId' => $property->mediaId ?? null,
                            ]
                        ),

                        'propertyGroups' => [
                            'name' => $product->propertyGroup->name ?? null,
                            'id' => $product->propertyGroup->id ?? null,
                        ],

                        'metaTitle' => $product->metaTitle ?? null,
                        'dreiscSeoTitle' => $product->mainDetail->attribute->dreiscSeoTitle ?? null,
                        'image_ids' => collect($product->images)->map->mediaId,
                        'category_ids' => collect($product->categories)->map(fn ($category) => $category->id),
                        'related' => collect($product->related)->map(fn ($related) => $related->id),
                    ]
                ),
        ];
    }
}
