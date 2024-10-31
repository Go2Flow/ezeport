<?php

namespace Go2Flow\Ezport\Helpers\Getters\Uploads\ShopwareSix;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Setters\Set;


class ArticleMedia extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers,
        StandardShopSixArticle,
        ArticleFields,
        GeneralFields;

    public function get() : array
    {
        return [
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
        ];
    }
}
