<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\ShopwareFive;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class Categories extends BaseInstructions implements InstructionInterface {

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
        ];
    }
}
