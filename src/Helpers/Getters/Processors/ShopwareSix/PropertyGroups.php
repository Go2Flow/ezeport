<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class PropertyGroups extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers, StandardShopSixArticle;

    public function get() : array
    {
        return [
            Set::UploadProcessor('propertyGroup')
                ->process(
                    function (Collection $items, Api $api, array $config) {

                        if ($items->isEmpty()) return;

                        $data = $items->toShopArray();

                        $groupId = null;
                        foreach ($data as $item) {

                            if (isset($item['groupId'])) {
                                $config['shopware']['id'] = $item['groupId'];
                                break;
                            }
                        }

                        if (!isset($config['shopware'])) {

                            $config['shopware'] = $this->getOrCreatePropertyGroup(
                                $config['group'],
                                $api
                            );
                        }

                        $response = $this->createOrUpdatePropertyGroupOptions(
                            $config['shopware'],
                            $data,
                            $api
                        );

                        if ($response->status() == 200) {
                            $this->setPropertyOptionId(
                                $response,
                                $items,
                                $config['stored_id_type'] ?? 'id'
                            );
                        } else {
                            $items->each(fn ($item) => $item->logError([
                                'reason' => 'failed to upload property group options',
                                'api-error-messages' => $api->getClient()->getErrorMessages()
                            ]));
                        }
                    }
                ),
        ];
    }
}
