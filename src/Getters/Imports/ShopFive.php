<?php

namespace Go2Flow\Ezport\Getters\Imports;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class ShopFive extends BaseInstructions implements InstructionInterface {

    public function get() : array
    {
        return [
            Set::ShopImport('Categories')
                ->type('Category')
                ->uniqueId('id')
                ->job(
                    Set::Job()
                        ->config([
                            'type' => 'categories'
                        ])
                )->api(Get::api('shopFive'))
                ->items(
                    fn (Api $api): Collection => collect(
                        $api->category()
                            ->get()
                            ->body()
                            ->data
                    )->pluck('id')
                )->process(
                    fn (Collection $chunk, Api $api): Collection => $chunk->map(
                        fn ($id) => $api->category()->find($id)->body()->data
                    )
                )->properties(
                    fn ($category) => [
                        'longDescription' => $category->cmsText ?? null,
                        'name' => $category->name,
                        'category_id' => $category->parentId,
                        'metaDescription' => $category->metaDescription,
                        'dreiscSeoUrl' => $category->attribute->dreiscSeoUrl ?? null,
                        'dreiscSeoTitle' => $category->attribute->dreiscSeoTitle ?? null,
                        'subheadlineTeaser' => $category->attribute->subheadlineTeaser ?? null,
                        'moreSeoText' => $category->attribute->sidebarCategoryDescription ?? null,
                    ]
                ),
            Set::ShopImport('Images')
                ->type('Image')
                ->api(Get::api('shopFive'))
                ->uniqueId('id')
                ->job(
                    Set::Job()
                        ->config([
                            'type' => 'images'
                        ])
                )->items(
                    fn (Api $api): Collection => collect($api->media()->limit(7000)->get()->body()->data)->pluck('id')
                )->process(
                    fn (Collection $chunk, Api $api): Collection => $chunk->map(
                        fn ($id) => $api->media()->find($id)->body()->data
                    )
                )->properties(
                    fn ($image) => [
                        'id' => $image->id,
                        'name' => $image->name,
                        'description' => $image->description,
                        'path' => $image->path,
                        'extension' => $image->extension,
                        'album_id' => $image->albumId,
                    ]
                ),
            Set::ShopImport('Manufacturers')
                ->type('Manufacturer')
                ->uniqueId('id')
                ->api(Get::api('shopFive'))
                ->job(
                    Set::Job()
                        ->config([
                            'type' => 'manufacturers'
                        ])
                )->items(
                    fn (Api $api): Collection => collect($api->manufacturer()->get()->body()->data)->pluck('id')
                )->process(
                    fn (Collection $chunk, Api $api): Collection => $chunk->map(
                        fn ($id) => $api->manufacturer()->find($id)->body()->data
                    )
                )->properties(
                    fn ($manufacturer) => [
                        'id' => $manufacturer->id,
                        'text' => $manufacturer->name,
                    ]
                ),
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
                        'supplier' => $product->supplier->name ?? null,

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

                        'suplier' => [
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
