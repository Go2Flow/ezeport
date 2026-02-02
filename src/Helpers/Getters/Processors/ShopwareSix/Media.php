<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class Media extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers, StandardShopSixArticle;

    public function get() : array
    {
        return [
            Set::UploadProcessor('media')
                ->process(
                    function (Collection $items, Api $api) {

                        $response = $api->media()
                            ->bulk(
                                $items->toShopArray()
                            )->body();

                        if ($response) {

                            for ($i = 0; $i < count($items); $i++) {
                                $items[$i]->shopware([
                                    'id' => $response->data->media[$i]
                                ]);
                                $items[$i]->updateOrCreate();
                            }
                        }
                        else {
                            $items->each(fn ($item) => $item->logError([
                                'reason' => 'failed to create or update media',
                                'api-error-messages' => $api->getClient()->getErrorMessages()
                            ]));
                        }

                    }
                ),
        ];
    }
}
