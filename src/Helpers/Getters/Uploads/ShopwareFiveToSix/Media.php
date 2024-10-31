<?php

namespace Go2Flow\Ezport\Helpers\Getters\Uploads\ShopwareFiveToSix;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Setters\Set;

class Media extends BaseInstructions implements InstructionInterface {

    use GeneralHelpers,
        ArticleFields,
        GeneralFields;

    public function get() : array{

        return [
            Set::Upload('media')
                ->items(fn () => Content::type('Image', $this->project))
                ->fields([
                    ['title' => fn ($item) => $item->properties('name')],
                    ['name' => fn ($item) => $item->properties('name')],
                    Set::UploadField('mediaFolder')
                        ->field(fn ($item) => [
                            'id' => $this->project->cache('media_folder_ids')['standard']
                        ]),
                    $this->setShopwareIdField()
                ]),
        ];
    }
}

