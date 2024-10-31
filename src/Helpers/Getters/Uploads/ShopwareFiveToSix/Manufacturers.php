<?php

namespace Go2Flow\Ezport\Helpers\Getters\Uploads\ShopwareFiveToSix;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Setters\Set;

class Manufacturers extends BaseInstructions implements InstructionInterface {

    use GeneralHelpers,
        ArticleFields,
        GeneralFields;

    public function get() : array{

        return [
            Set::Upload('Manufacturers')
                ->items(fn () => Content::type('Manufacturer', $this->project))
                ->field(['name' => fn ($item) => $item->properties('text')])
                ->field($this->setShopwareIdField()),
        ];
    }
}

