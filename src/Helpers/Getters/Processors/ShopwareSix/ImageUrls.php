<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class ImageUrls extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers, StandardShopSixArticle;

    public function get() : array
    {
        return [
            Set::UploadProcessor('ImageUrl')
                ->process(
                    fn (Collection $items, Api $api) => $items->each(
                        function ($item) use ($api) {
                            $array = $item->toShopArray();
                            $api->media()
                                ->url(
                                    $array['path'],
                                    $array['id'],
                                    $array['extension'] ?? 'jpg'
                                );
                        }
                    )
                ),
        ];
    }
}
