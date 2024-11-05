<?php

namespace Go2Flow\Ezport\Helpers\Getters\Uploads\ShopwareFiveToSix;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Models\GenericModel;
use Illuminate\Support\Str;

class Articles extends BaseInstructions implements InstructionInterface {

    use GeneralHelpers,
        ArticleFields,
        GeneralFields;

    public function get() : array{

        return [
            Set::Upload('articles')
                ->items(fn () => GenericModel::whereType('Article')
                    ->whereProjectId($this->project->id)
                    ->whereUpdated(1)
                    ->wheretouched(1)
                    ->pluck('id')
                )->chunk(20)
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
                    ['metaDescription' => fn ($item) => Str::limit($item->properties('description'), 250)],

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
        ];
    }
}

