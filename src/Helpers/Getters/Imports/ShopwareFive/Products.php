<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\ShopwareFive;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Setters\Set;

class Products extends BaseInstructions implements InstructionInterface {

    public function get() : array
    {
        return [
            Set::ShopImport('Products')
                ->api(Get::api('shopFive'))
                ->items(
                    fn ($api) => collect(
                        $api->product()
                            ->limit(15000)
                            ->get()
                            ->body()
                            ->data
                    )->pluck('id')
                )->process(
                    function ($chunk, $api) {

                        foreach ($chunk as $id) {
                            $product = $api->product()->find($id)->body()->data;

                            Content::Type('Article', $this->project)
                                ->updateOrCreate([
                                    'unique_id' => $product->id,
                                ], [
                                    'name' => $product->name ?? null,
                                    'properties' => [
                                        'articleNumber' => $product->mainDetail->number ?? null,
                                        'description' => $product->description ?? null,
                                        'descriptionLong' => $product->descriptionLong ?? null,
                                        'active' => $product->active ?? null,
                                        'ean' => $product->mainDetail->ean ?? null,
                                        'stocks' => $product->mainDetail->inStock ?? 0,
                                        'minimumStock' => $product->mainDetail->stockMin ?? 0,

                                        'propertyValues' => $product->propertyValues,

                                        'prices' => collect($product->mainDetail->prices ?? [])->map(
                                            fn ($price) => [
                                                'price' => $price->price,
                                                'articleDetailsId' => $price->articleDetailsId,
                                                'from' => $price->from,
                                                'to' => $price->to,
                                                'customerGroup' => [
                                                    'key' => $price->customerGroup->key,
                                                    'name' => $price->customerGroup->name,
                                                ]
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
                                        'unitId' => $product->mainDetail?->unitId,

                                        'images' => collect($product->images)->map(
                                            fn($image) => [
                                                'id'=>  $image->mediaId,
                                                'position' => $image->position,
                                                'cover' => $image->main,
                                            ]
                                        ),

                                        'children' =>
                                            collect($product->details ?? [])->map(
                                                fn ($variant) => [
                                                    'articleNumber' =>$variant->number ?? null,
                                                    'name' => $variant->name ?? null,
                                                    'description' => $variant->description ?? null,
                                                    'descriptionLong' => $variant->descriptionLong ?? null,
                                                    'active' => $variant->active ?? null,
                                                    'ean' => $variant->ean ?? null,
                                                    'stocks' => $variant->inStock ?? 0,
                                                    'minimumStock' => $variant->stockMin ?? 0,
                                                    'configuratorOptions' => $variant->configuratorOptions,
                                                    'prices' => collect($variant->prices ?? [])->map(
                                                        fn ($price) => [
                                                            'price' => $price->price,
                                                            'articleDetailsId' => $price->articleDetailsId,
                                                            'from' => $price->from,
                                                            'to' => $price->to,
                                                            'customerGroup' => [
                                                                'key' => $price->customerGroup->key,
                                                                'name' => $price->customerGroup->name,
                                                            ]
                                                        ]
                                                    ),

                                                ])
                                    ]
                                ]);
                        }
                    }
                ),
        ];
    }
}
