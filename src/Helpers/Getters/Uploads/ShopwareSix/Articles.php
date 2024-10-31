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

class Articles extends BaseInstructions implements InstructionInterface
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
        ];
    }
}
