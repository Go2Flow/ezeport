<?php


namespace Go2Flow\Ezport\Helpers\Getters\Uploads\ShopwareSix;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Setters\Set;

class Customers extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers,
        StandardShopSixArticle,
        ArticleFields,
        GeneralFields;

    public function get(): array
    {
        return [
            Set::upload('customers')
                ->fields([
                    $this->setShopwareUploadField(),
                    ['email' => fn ($item) => $item->properties('email')],
                    ['firstName' => fn ($item) => $item->properties('name')['first'] ?? 'empty'],
                    ['lastName' => fn ($item) => $item->properties('name')['last']],
                    ['salutationId' => fn ($item) => $this->project->cache('salutation_ids')[$item->properties('salutation')]],
                    ['accountType' => 'bussiness'],
                    Set::UploadField()
                        ->field( function ($item) {
                            $address = [

                                'countryId' => $this->project->cache('country_ids')['ch'],
                                'firstName' => $item->properties('name')['first'] ?? 'empty',
                                'lastName' => $item->properties('name')['last'] ?? 'last',
                                'street' => $item->properties('address')['street'],
                                'zipcode' => (string) $item->properties('address')['zip'],
                                'city' => $item->properties('address')['city'],
                                'company' => $item->properties('company'),
                            ];

                            return [
                                'defaultShippingAddress' => array_merge($address, $item->shopware('shippingId') ? ['id' => $item->shopware('shippingId')] : []),
                                'defaultBillingAddress' => array_merge($address, $item->shopware('billingId') ? ['id' => $item->shopware('billingId')] : []),
                            ];
                        }),
                    ['customerNumber' => fn ($item) => $item->unique_id],
                    ['active' => fn ($item) => true],
                    ['salesChannelId' => fn () => $this->project->cache('sales_channel_ids')['standard']],
                    ['boundSalesChannelId' => fn () => $this->project->cache('sales_channel_ids')['standard']],
                    ['languageId' => fn () => $this->project->cache('language_ids')['de']],
                    ['defaultPaymentMethodId' => fn () => $this->project->cache('payment_method_ids')['standard']],
                    ['groupId' => fn () => '018d8e3654ff78d38abc4254a37a5b8d'],
                    ['company' => fn ($item) => $item->properties('company')]
                ]),
        ];
    }
}
