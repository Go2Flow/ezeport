<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class Categories extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers, StandardShopSixArticle;

    public function get() : array
    {
        return [
            Set::UploadProcessor('Category')
                ->process(
                    function (Collection $items, Api $api) {
                        foreach ($items as $category) {

                            $array = $category->toShopArray();

                            if (!isset($array['parentId'])) continue;

                            $response = $api->category()
                                ->{$category->shopware('id') ? 'patch' : 'create'}(
                                    $array,
                                    $category->shopware('id')
                                )->body();

                            if ($response) {
                                $category->shopware(['id' => $response->data->id]);
                                $category->updateOrCreate();
                            }
                            else {
                                $category->logError([
                                    'reason' => 'failed to upload category',
                                    'api-error-messages' => $api->getClient()->getErrorMessages()
                                ]);
                            }
                        }
                    }
                ),
        ];
    }
}
