<?php

namespace Go2Flow\Ezport\Helpers\Getters\Uploads;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Models\GenericModel;

class ShopwareFiveToSix extends BaseInstructions implements InstructionInterface {

    use GeneralHelpers,
        ArticleFields,
        GeneralFields;

    public function get() : array{

        return [
            Set::Upload('categories')
            ->items(
                function () {
                    $assigned = collect();
                    $models = GenericModel::whereType('Category')
                        ->whereProjectId($this->project->id)
                        ->whereUpdated(true)
                        ->doesntHave('children')
                        ->get();

                    while ($models->count()) {
                        $assigned->push(...$models->pluck('id'));

                        $models = $models->flatMap(
                            fn ($item) => $item->parents()
                                ->whereProjectId($this->project->id)
                                ->whereUpdated(true)
                                ->whereType('Category')
                                ->get()
                        )->filter();
                    }

                    return $assigned;
                }
            )->fields([
                ['type' => 'page'],
                ['productAssignmentType' => 'product'],
                ['displayNestedProducts' => true],
                $this->setBasicUploadField('name'),
                $this->setCategoryCmsPageIdField(),
                $this->setShopwareUploadField(),
                Set::UploadField('parentId')
                    ->field(
                        function ($item) {

                            if (!($categories = $item->relations('categories'))) {
                                return $this->project->cache('category_ids')['parent'];
                            }

                            if ($categories->first()->shopware('id')) {
                                return $categories->first()->shopware('id');
                            }
                            if ($id = $categories->first()->refresh()->shopware('id')) {
                                return $id;
                            }
                        }

                    ),
                $this->setBasicUploadField('metaDescription'),
                $this->setBasicUploadField('metaTitle', 'dreiscSeoTitle'),
                $this->setBasicUploadField('categoryDescription', 'longDescription'),
            ]),
            Set::Upload('articles')
                ->fields([
                    $this->setShopwareUploadField(),
                    Set::PriceField('price')
                        ->price(fn ($item) => $item->properties('prices')[0]['price'] ?? 0),
                    $this->setBasicUploadField('name'),
                    ['stock' => fn ($item) => $item->properties('stocks') < 0 ? 0 : $item->properties('stocks')],
                    $this->setBasicUploadField('productNumber', 'articleNumber'),
                    [
                        'manufacturerId' => fn ($item) => Content::type('Manufacturer', $this->project)
                            ->first()
                            ?->shopware('id')
                    ],
                    $this->setBasicUploadField('description', 'descriptionLong'),
                    $this->setBasicUploadField('ean'),
                    $this->setBasicUploadField('metaTitle', 'dreiscSeoTitle'),
                    ['metaDescription' => fn ($item) => substr($item->properties('description'), 0, 200)],
                    Set::UploadField()
                        ->field(
                            function ($item) {
                                if (!$dimensions = $item->properties('dimensions')) return null;

                                $array = [];
                                foreach (['width', 'height', 'length', 'weights'] as $key) {
                                    if (!isset($dimensions[$key])) continue;

                                    $array[$key] = $dimensions[$key];
                                }

                                return $array;
                            }
                        ),
                    Set::UploadField('translations')
                        ->field(
                            function ($item) {

                                $translations = [];

                                foreach (['fr-CH' => 'French', 'en-GB' => 'English'] as $lang => $key) {
                                    if ($translation = $item->properties('translations')[$key] ?? null) {
                                        $translations[$lang] = [
                                            'metaDescription' => $translation['description'],
                                            'description' => $translation['descriptionLong'],
                                            'name' => $translation['name'],

                                        ];
                                    }
                                }
                                if ($translations) return $translations;
                            }
                        ),

                ]),
            Set::Upload('Manufacturers')
                ->items(fn () => Content::type('Manufacturer', $this->project))
                ->field(['name' => fn ($item) => $item->properties('text')])
                ->field($this->setShopwareIdField()),

            Set::Upload('media')
                ->items(fn () => Content::type('Image', $this->project))
                ->fields([
                    ['title' => fn ($item) => $item->properties('name')],
                    ['name' => fn ($item) => $item->properties('name')],
                    Set::UploadField('mediaFolder')
                        ->field(fn ($item) => [
                            'id' => $this->project->cache('media_folder_ids')['standard']
                        ]),
                    $this->setShopwareIdField()
                ]),
            Set::Upload('CrossSellings')
                ->items(fn () => Content::type('Article', $this->project))
                ->field(
                    Set::UploadField()
                        ->field(
                            function ($item) {

                                $ids = $item->properties('related')
                                    ?->map(fn ($item) => Content::type('Article', $this->project)->find($item)?->shopware('id'))
                                    ->filter()
                                    ->values();

                                if (! $ids ||  $ids->count() == 0) return [];

                                return [
                                    'productId' => $item->shopware('id'),
                                    'name' => 'Modellfamilie',
                                    'position' => 1,
                                    'type' => 'productList',
                                    'active' => true,
                                    'assignedProducts' => $ids->map(fn ($selling, $key) => [
                                        'productId' => (string) $selling,
                                        'position' => $key + 1,
                                    ])->toArray()
                                ];
                            }
                        )
                ),
            Set::Upload('Units')
                ->fields([
                    $this->setBasicUploadField(
                        'name'
                    ),
                    $this->setShopwareUploadField(),
                    ['shortCode' => fn ($item) => $item->properties('shortCode')]
                ]),
        ];
    }
}

