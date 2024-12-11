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

                        $ids = $items->flatmap(
                            fn($item, $key) => $item->shop("article_media")
                        )->filter();

                        if ($ids->count() > 0) {

                            $api->productMedia()->bulkDelete($ids->map(fn ($id) => ['id' => $id ])->values()->toArray());
                        }

                        $response = $api->productMedia()
                            ->bulk(
                                $items->flatMap(
                                    fn ($item) => $item->toShopArray()
                                )->values()->toArray()
                            )->body();

                        if ($response) {

                            for ($i = 0; $i < $items->count(); $i ++) {

                                $items[$i]->shop(['article_media' => $response->data->product_media[$i]]);
                                $items[$i]->updateOrCreate();

                            }
                        }
                    }
                ),
        ];
    }
}
