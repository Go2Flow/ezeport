<?php

namespace Go2Flow\Ezport\Helpers\Getters\Uploads;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Models\GenericModel;
use Illuminate\Support\Collection;

class StandardShopSix extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers,
        StandardShopSixArticle,
        ArticleFields,
        GeneralFields;

    public function get() : array
    {
        return [
            Set::Upload('articles')
                ->items(
                    function () {
                        $ids = collect();

                        Content::type("Article", $this->project)
                            ->whereUpdated(true)
                            ->whereTouched(true)
                            ->chunk(100, fn ($items) => $ids->push($items->pluck("id")));

                        return $ids->flatten();
                    }
                )->fields([
                    $this->setShopwareIdField(),
                    $this->setBasicUploadField('name'),
                    ...$this->setStandardArticleFields([
                        'taxId',
                        'productCmsPageId',
                        'visibilities',
                        'categories',
                        'currencyId',
                    ]),
                ]),
            Set::Upload('categories')
                ->items(
                    function () {
                        $assigned = collect();
                        $models = GenericModel::whereType('Category')
                            ->whereProjectId($this->project->id)
                            ->doesntHave('children')
                            ->get();

                        while ($models->count()) {
                            $assigned->push(...$models->pluck('id'));

                            $models = $models->flatMap(
                                fn ($item) => $item->parents()->whereProjectId($this->project->id)->get()
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
                    $this->setShopwareIdField(),
                ]),
            Set::Upload('CrossSellings')
                ->items(fn () => Content::type('Article', $this->project))
                ->field(
                    Set::UploadField()
                        ->field(
                            fn (Generic $item, array $config) => collect($config['types'])
                                ->map(
                                    function ($type, $key) use ($item) {

                                        if (!$item->shopware('id')) return;

                                        $sellings = $item->properties($type)
                                            ?->map(
                                                fn ($selling) => Content::type('Article', $item->project())
                                                    ->find($selling['article_id'])
                                                    ?->shopware('id')
                                            )->filter();



                                        if (!$sellings || $sellings->count() === 0) return [];

                                        return array_merge([
                                            'productId' => $item->shopware('id'),
                                            'name' => $key,
                                            'position' => 1,
                                            'type' => 'productList',
                                            'active' => true,
                                            'assignedProducts' => $sellings->values()
                                                ->map(fn ($selling, $num) => [
                                                    'productId' => (string) $selling,
                                                    'position' => $num + 1,
                                                ])->toArray()
                                        ], $item->shopware('cross_sellings')?->has($key) ? [
                                            'id' => $item->shopware('cross_sellings')[$key]
                                        ] : []);
                                    }
                                )->filter()
                                ->toArray()

                        )
                ),
            Set::Upload('articleMedia')
                ->items(fn () => Content::type('Article', $this->project))
                ->field(
                    Set::uploadField()
                        ->field(
                            fn ($item) => $item->relations('images')
                                ?->map(
                                    fn ($image, $key) => array_merge([
                                        'mediaId' => $image->shopware('id'),
                                        'productId' => $item->shopware('id'),
                                    ], $key == 0 ? ['coverProducts' => [['id' => $item->shopware('id')]]] : [])
                                )
                        )

                ),
            Set::Upload('Manufacturers')
                ->items(fn () => Content::type('Manufacturer', $this->project))
                ->fields([
                    ['name' => fn ($item) => $item->properties('text')],
                    $this->setShopwareIdField(),
                ]),

            Set::Upload('ImageUrl')
                ->items(fn () => Content::type('Image', $this->project))
                ->field($this->setBasicUploadField('path'))
                ->field($this->setShopwareIdField()),

            Set::Upload('PropertyGroups')
                ->fields([
                    $this->setBasicUploadField('name', 'text'),
                    $this->setShopwareIdField()
                ])->processor(
                    Get::Processor('PropertyGroup')
                        ->config(['group' => 'Group'])
                ),
            Set::Upload('shopOrderTransitions')
                ->items(fn () => Content::type('Order', $this->project))
                ->processor(
                    Set::UploadProcessor()
                        ->process(
                            function (Collection $items, Api $api) {

                                foreach ($items as $item) {

                                    if ($item->shopware('ftp') == 'uploaded' || !$item->shopware('id')) continue;

                                    $api->order()->transition($item->shopware('id'), 'process');
                                    $api->order()->transition($item->shopware('id'), 'complete');

                                    $item->updateOrCreate(false);
                                }
                            }
                        )
                ),
        ];
    }
}
