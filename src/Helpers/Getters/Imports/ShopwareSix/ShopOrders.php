<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\ShopwareSix;

use Carbon\Carbon;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class ShopOrders  extends BaseInstructions implements InstructionInterface {


    public function get() : array
    {
        return [
            Set::ShopImport('shopOrders')
                ->type('Order')
                ->api(Get::api('shopSix'))
                ->job(
                    Set::Job()
                        ->config([
                            'type' => 'orders'
                            ])
                )->uniqueId('orderNumber')
                ->items(
                    fn (Api $api) : Collection => collect(
                        $api->order()
                            ->filter([
                                'type' => 'equalsAny',
                                'field' => 'transactions.stateMachineState.id',
                                'value' => $this->project->cache('order_transaction_ids')['paid'],
                            ])->search()
                            ->body()
                            ->data
                    )
                )->process(
                    fn (Collection $chunk, Api $api) => collect(
                        $api->order()
                            ->filter([
                                'type' => 'equalsAny',
                                'field' => 'id',
                                'value' => $chunk->pluck('id')->toArray(),
                            ])->association([
                                'language' => [
                                    'associations' => [
                                        'translationCode' => []
                                    ]
                                ],
                                'deliveries' => [
                                    'associations' => [
                                        'shippingOrderAddress' => [
                                            'associations' => [
                                                'country' => [],
                                                'salutation' => []
                                            ]
                                        ]
                                    ]
                                ],
                                'billingAddress' => [
                                    'associations' => [
                                        'country' => [],
                                        'salutation' => []
                                    ]
                                ],
                                'transactions' => [
                                    'associations' => [
                                        'paymentMethod' => []
                                    ],
                                ],
                                'lineItems' => [
                                    'associations' => [
                                        'product' => []
                                    ]
                                ],
                                'orderCustomer' => [
                                    'associations' => [
                                        'salutation' => []
                                    ]
                                ]
                            ])->search()
                            ->body()
                            ?->data
                    )
                )->properties(
                    fn ($item) => [
                        'Customer' => [
                            'FirstName' => $item->orderCustomer->firstName,
                            'LastName' => $item->orderCustomer->lastName,
                            'Salutation' => $item->orderCustomer->salutation->salutationKey,
                            'CustomerNumber' => $item->orderCustomer->customerNumber,
                            'Email' => $item->orderCustomer->email,
                            'CustomerId' => $item->orderCustomer->id,
                        ],
                        'Positions' => collect($item->lineItems)->map(
                            fn ($lineItem) => [
                                'Position' => [
                                    'ArticleNumber' => $lineItem->payload->productNumber,
                                    'Quantity' => $lineItem->quantity,
                                    'unitPrice' => $lineItem->price->unitPrice,
                                    'UnitPriceNet' => $lineItem->product->price[0]->gross,
                                    'UnitPriceTax' => $lineItem->price->unitPrice - $lineItem->product->price[0]->gross,
                                    'Tax' => $this->project->settings('taxes')['standard'],
                                    'ProductName' => $lineItem->product->name,
                                    'ean' => $lineItem->product->ean,
                                    'PositionId' => $lineItem->payload->productNumber,
                                ]
                            ]
                        ),
                        'Update' => 0,
                        'InvoiceAmount' => $item->amountTotal,
                        'InvoiceAmountNet' => $item->amountNet,
                        'OrderDate' => Carbon::create($item->orderDateTime),
                        'OrderNumber' => $item->orderNumber,
                        'LanguageShop' => $item->language->translationCode->code,

                        'InvoiceAddress' => [
                            'Address' => $item->billingAddress->street,
                            'Company' => $item->billingAddress->company,
                            'Salutation' => $item->billingAddress->salutation->salutationKey,
                            'FirstName' => $item->billingAddress->firstName,
                            'LastName' => $item->billingAddress->lastName,
                            'City' => $item->billingAddress->city,
                            'PostalCode' => $item->billingAddress->zipcode,
                            'Country' => $item->billingAddress->country->iso,
                            'AdditionalInfo' => $item->billingAddress->additionalAddressLine1,
                        ],

                        'ShippingAddress' => [
                            'Address' => $item->deliveries[0]->shippingOrderAddress->street,
                            'Company' => $item->deliveries[0]->shippingOrderAddress->company,
                            'Salutation' => $item->deliveries[0]->shippingOrderAddress->salutation->salutationKey,
                            'FirstName' => $item->deliveries[0]->shippingOrderAddress->firstName,
                            'LastName' => $item->deliveries[0]->shippingOrderAddress->lastName,
                            'City' => $item->deliveries[0]->shippingOrderAddress->city,
                            'PostalCode' => $item->deliveries[0]->shippingOrderAddress->zipcode,
                            'Country' => $item->deliveries[0]->shippingOrderAddress->country->iso,
                            'AdditionalInfo' => $item->deliveries[0]->shippingOrderAddress->additionalAddressLine1,
                        ],
                        'Shipping' => [
                            'Partner' => 'boop',
                            'UnitPrice' => $item->shippingCosts->unitPrice,
                            'UnitPriceNet' => $item->shippingCosts->totalPrice * (1 + $this->project->settings('taxes')['standard']),
                            'UnitPriceTax' => $this->project->settings('taxes')['standard'],
                        ],
                        'Payment' => [
                            'Partner' => $item->transactions[0]->paymentMethod->name,
                            'Status' => 1,
                        ]
                    ]
                )->shop(
                    fn ($item) => [
                        'id' => $item->id,
                        'products' => collect($item->lineItems)
                            ->mapWithKeys(
                                fn ($lineItem) => [
                                    $lineItem->productId => [
                                        'id' => $lineItem->productId,
                                        'optionsId' => $lineItem->payload->optionIds[0] ?? null,
                                    ]
                                ]
                            ),
                        'state' => $item->stateMachineState->_uniqueIdentifier,
                    ]
                ),
        ];
    }
}
