<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class ArticleMedia extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers, StandardShopSixArticle;

    public function get() : array
    {
        return [
            Set::UploadProcessor('articleMedia')
                ->process(
                    function (Collection $items, Api $api) {
                        $images = $this->getUniqueImages(
                            collect($items->toFlatShopArray()),
                            $api
                        )->filter()
                            ->values();

                        if (count($images) == 0) return;

                        $api->productMedia()
                            ->bulk($images->toArray())
                            ->body();
                    }
                ),
        ];
    }
}
