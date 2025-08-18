<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\ShopwareFive;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
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
                ->api(Get::api('shopFive'))
                ->items(
                    function ($api) {
                        return collect(
                            $api->category()
                                ->get()
                                ->body()
                                ->data
                        )->pluck('id');
                    }
                )->process(
                    function ($chunk, $api) {

                        foreach ($chunk as $id) {
                            $category = $api->category()->find($id)->body()->data;

                            Content::Type('Category', $this->project)
                                ->updateOrCreate([
                                    'unique_id' => $category->id
                                ], [
                                    'name' => $category->name,
                                    'properties' => [
                                        'longDescription' => $category->cmsText ?? null,
                                        'category_id' => $category->parentId,
                                        'dreiscSeoUrl' => $category->attribute->dreiscSeoUrl ?? null,
                                        'dreiscSeoTitle' => $category->attribute->dreiscSeoTitle ?? null,
                                        'subheadlineTeaser' => $category->attribute->subheadlineTeaser ?? null,
                                        'metaTitle' => $category->metaTitle ?? null,
                                        'metaKeywords' => $category->metaKeywords ?? null,
                                        'metaDescription' => $category->metaDescription ?? null,
                                        'media_id' => $category->mediaId,
                                    ]
                                ]);
                        }
                    }
                ),
        ];
    }
}
