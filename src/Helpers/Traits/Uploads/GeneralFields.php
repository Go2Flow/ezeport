<?php

namespace Go2Flow\Ezport\Helpers\Traits\Uploads;

use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadField;

trait GeneralFields {

    use FieldHelpers;

    /**
     * returns the cached id from the project object stored in the 'cms_page_ids' array under the key 'category'
     */

    protected function setCategoryCmsPageIdField() : UploadField
    {
        return $this->getFromProject('cmsPageId', 'cms_page_ids', 'category');
    }

    /**
     * looks in the item's properties for the $field and returns it with the key $name
     * If no field is provided, the $name is used as the field
     */

    protected function setBasicUploadField(string $name, ?string $field = null) : UploadField
    {
        return $this->setAbstractUploadField('properties', $name, $field ?? $name);
    }

    protected function setShopwareIdField() : UploadField
    {
        return $this->setShopwareUploadField('id', 'id');
    }

    protected function setShopwareUploadField(string $to = 'id', string $from = 'id') : UploadField
    {
        return $this->setAbstractUploadField('shopware', $to, $from);
    }

    private function setAbstractUploadField(string $type, string $name, string $field) : UploadField
    {
        return Set::UploadField($name)
            ->field(
                fn ($item) => $item->$type($field)
            );
    }

}
