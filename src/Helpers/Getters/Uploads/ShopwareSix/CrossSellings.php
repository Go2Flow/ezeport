<?php

namespace Go2Flow\Ezport\Helpers\Getters\Uploads\ShopwareSix;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Setters\Set;

class CrossSellings extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers,
        StandardShopSixArticle,
        ArticleFields,
        GeneralFields;

    public function get() : array
    {
        return [
            Set::Upload('CrossSellings')
                ->items(fn () => Content::type('Article', $this->project))
                ->field(
                    Set::UploadField()
                        ->field(
                            fn (Generic $item, array $config) => collect($config['types'])
                                ->map(
                                    function ($type, $key) use ($item) {

                                        if (!$item->shop('id')) return;

                                        $sellings = $item->properties($type)
                                            ?->map(
                                                fn ($selling) => Content::type('Article', $item->project())
                                                    ->find($selling['article_id'])
                                                    ?->shop('id')
                                            )->filter();

                                        if (!$sellings || $sellings->count() === 0) return [];

                                        return array_merge([
                                            'productId' => $item->shop('id'),
                                            'name' => $key,
                                            'position' => 1,
                                            'type' => 'productList',
                                            'active' => true,
                                            'assignedProducts' => $sellings->values()
                                                ->map(fn ($selling, $num) => [
                                                    'productId' => (string) $selling,
                                                    'position' => $num + 1,
                                                ])->toArray()
                                        ], $item->shop('cross_sellings')?->has($key)
                                            ? ['id' => $item->shop('cross_sellings')[$key]]
                                            : []
                                        );
                                    }
                                )->filter()
                                ->toArray()

                        )
                )
        ];
    }
}
