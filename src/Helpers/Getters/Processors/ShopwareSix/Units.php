<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class Units extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers, StandardShopSixArticle;

    public function get() : array
    {
        return [
            Set::UploadProcessor('Units')
                ->process(
                    fn (Collection $items, Api $api) => $items->each(
                        function ($item) use ($api) {

                            $response = $api->unit()->{$item->shopware('id') ? 'patch' : 'create'}(
                                $item->toShopArray(),
                                $item->shopware('id')
                            )->body();

                            if (!$response) {
                                $item->shopware(['id' => $response->data->id]);
                                $item->updateOrCreate();
                            }
                            else {
                                $item->logError([
                                    'reason' => 'failed to upload or create unit',
                                    'api-error-messages' => $api->getClient()->getErrorMessages()
                                ]);
                            }
                        }
                    )
                )
        ];
    }
}
