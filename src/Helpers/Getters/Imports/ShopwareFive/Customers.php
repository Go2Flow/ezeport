<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\ShopwareFive;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Setters\Set;

class Customers extends BaseInstructions implements InstructionInterface {

    public function get() : array {
        return [
            Set::ShopImport('Customers')
                ->api(Get::api('shopFive'))
                ->items(
                    fn ($api) => collect($api->customers()->get()->body()->data)->pluck('id')
                )->process(
                    function ($chunk, $api) {

                        foreach ($chunk as $id) {
                            $customer = $api->customers()->find($id)->body()->data;

                            Content::Type('Customer', $this->project)
                                ->updateOrCreate([
                                    'unique_id' => $customer->id,
                                ], [
                                    'name' => $customer->firstname . ' ' . $customer->lastname,
                                    'properties' => [

                                        'email' => $customer->email,
                                        'firstName' => $customer->firstname,
                                        'name' => $customer->firstname,
                                        'salutation' => $customer->salutation,
                                        'lastName' => $customer->lastname,
                                        'accountType' => 'personal',
                                        'country' => $customer->defaultBillingAddress->country->iso,
                                        'address' => [
                                            'street' => $customer->defaultBillingAddress->street,
                                            'zip' => $customer->defaultBillingAddress->zipcode,
                                            'city' => $customer->defaultBillingAddress->city,
                                            'country' => $customer->defaultBillingAddress->country->iso,
                                        ],
                                        'active' => $customer->active,
                                        'customer_group_id' => $customer->groupKey,
                                    ]
                                ]);
                        }
                    }),
            ];
    }
}



