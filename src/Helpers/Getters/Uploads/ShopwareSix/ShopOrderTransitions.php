<?php

namespace Go2Flow\Ezport\Helpers\Getters\Uploads\ShopwareSix;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class ShopOrderTransitions extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers,
        StandardShopSixArticle,
        ArticleFields,
        GeneralFields;

    public function get() : array
    {
        return [
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
