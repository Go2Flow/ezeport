<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class CustomerGroups extends BaseInstructions implements InstructionInterface
{
    public function get() : array
    {
        return [
            Set::UploadProcessor('CustomerGroups')
                ->process(
                    fn(Collection $items, Api $api) => $items->each(
                        function ($item) use ($api) {
                            $array = $item->toShopArray();

                            if ($item->shopware('id')) {
                                $response = $api->customerGroup()->patch(
                                    $array,
                                    $item->shopware('id')
                                );
                            } else {
                                $response = $api->customerGroup()->create($array);
                            }

                            if( $response->body()) {
                                $item->shopware(['id' => $response->body()->data->id]);
                                $item->updateOrCreate(false);
                            }
                            else {
                                $item->logError([
                                    'reason' => 'failed to upload customer group',
                                    'api-error-messages' => $api->getClient()->getErrorMessages()
                                ]);
                            }
                        }
                    )
                ),
            Set::UploadProcessor('rules')
                ->process(
                    fn (Collection $items, Api $api) => $items->each(
                        function ($item) use ($api) {
                            $array = [
                                "priority" => 999,
                                "name" => $item->toShopArray()['name'],
                                "position" => 0,
                                "conditions" => [
                                    [
                                        "type" => "andContainer",
                                        "value" => [],
                                        "position" => 0,
                                        "children" => [
                                            [
                                                "type" => "orContainer",
                                                "position" => 0,
                                                "children" => [
                                                    [
                                                        "type" => "customerCustomerGroup",
                                                        "position" => 0,
                                                        "value" => [
                                                            "operator" => "=",
                                                            "customerGroupIds" => [$item->shopware('id')]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ];

                            if (! $item->shopware('rule_id')) {
                                $response = $api->rule()->create($array);

                            } else {
                                $response = $api->rule()->patch($array, $item->shopware('rule_id'));
                            }

                            if ($response->body()) {
                                $item->shopware(['rule_id' => $response->body()->data->id]);
                                $item->updateOrCreate(false);
                            } else {
                                $item->logError([
                                    'reason' => 'failed to upload customer group rule',
                                    'api-error-messages' => $api->getClient()->getErrorMessages()
                                ]);
                            }

                        }
                    )
                )
        ];
    }
}

