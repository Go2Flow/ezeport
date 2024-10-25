<?php

namespace Go2Flow\Ezport\Helpers\Traits\Uploads;

use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadField;
use Go2Flow\Ezport\Process\Errors\EzportSetterException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait ArticleFields
{
    use FieldHelpers;

    /**
     * pass in a collection of strings for fields you want to use the standard setup for
     * You don't need to include the 'set' or 'Field' part of the method name. These will be added if missing
     * the returned result must flattened to be used in the upload
     */

    protected function setStandardArticleFields(Collection|array $list) : Collection {

        return collect($list)->map(

            function ($item) {
                $item = Str::of($item);

                return match (true) {
                    $item->contains('productCmsPageId') => $this->setProductCmsPageIdField(),
                    $item->contains('taxId') => $this->setTaxIdField(),
                    $item->contains('currencyId') => $this->setCurrencyIdField(),
                    $item->contains('configurationSettings') => $this->setConfigurationSettingsField(),
                    $item->contains('visibilities') => $this->setVisibilitiesField(),
                    $item->contains('categories') => $this->setCategoriesField(),
                    default => throw new EzportSetterException('Method ' . $item . ' does not exist')
                };
            }
        );
    }

    /**
     * returns the cached id from the project object stored in the 'cms_page_ids' array under the key 'product'
     */

    protected function setProductCmsPageIdField(): UploadField
    {
        return $this->getFromProject('cmsPageId', 'cms_page_ids', 'product');
    }

    /**
     * returns the cached id from the project object stored in the 'tax_ids' array under the key 'standard'
     */

    protected function setTaxIdField(): UploadField
    {
        return $this->getFromProject('taxId', 'tax_ids', 'standard');
    }

    /**
     * returns the cached id from the project object stored in the 'currency_ids' array under the key 'standard'
     */

    protected function setCurrencyIdField(): UploadField
    {
        return $this->getFromProject('currencyId', 'currency_ids', 'standard');
    }

    /**
     * sets up the ConfigurationSettings field based on the options in the config attribute
     */

    protected function setConfigurationSettingsField(): UploadField
    {
        return Set::UploadField('configuratorSettings')
            ->field(
                fn ($item, $config) => collect($config['options'])->unique()
                    ->map(
                        fn ($option) => ['optionId' => $option]
                    )->toArray()
            );
    }

    protected function setVisibilitiesField(): UploadField
    {
        return Set::UploadField('visibilities')
            ->field(
                fn ($item) => [
                    [
                        'salesChannelId' => $item->project()->cache('sales_channel_ids')['standard'],
                        'visibility' => 30
                    ]
                ]
            );
    }

    protected function setPropertiesField($properties): UploadField
    {
        return Set::UploadField('properties')
            ->field(
                function ($item) use ($properties) {
                    $response = collect($properties)->flatMap(

                        fn ($relation) => $this->getCollectionFromRelation(
                            $item->relations($relation),
                            fn ($property) => [
                                'id' => $property->shopware('id'),
                                'groupId' => $property->shopware('group_id'),
                            ]
                        )
                    );

                    return [
                        'array' => $response->values()->toArray(),
                        'config' => [
                            'options' => $response->pluck('id')->values()->toArray(),
                        ]
                    ];
                }
            );
    }

    protected function setCategoriesField(): UploadField
    {
        return Set::UploadField('categories')
            ->field(
                fn ($category) => $this->getCollectionFromRelation(
                    $category->relations('categories'),
                    fn ($category) => [
                        'id' => $category->shopware('id'),
                    ]

                )->toArray()
            );
    }

    protected function checkDiscount($discount) : bool
    {
        return $discount && 0 != Str::replace(',', '',  $discount);
    }
}
