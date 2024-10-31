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

class Manufacturers extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers,
        StandardShopSixArticle,
        ArticleFields,
        GeneralFields;

    public function get() : array
    {
        return [
            Set::Upload('Manufacturers')
                ->items(fn () => Content::type('Manufacturer', $this->project))
                ->fields([
                    ['name' => fn ($item, $config) => $item->properties($config['name']?? 'name')],
                    $this->setShopwareIdField(),
                ]),
        ];
    }
}
