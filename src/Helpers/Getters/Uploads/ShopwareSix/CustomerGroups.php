<?php

namespace Go2Flow\Ezport\Helpers\Getters\Uploads\ShopwareSix;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Setters\Set;

class CustomerGroups extends BaseInstructions implements InstructionInterface
{

    use GeneralFields;
    public function get() : array
    {
        return [
            Set::upload('rules')
                ->items(
                    fn () => Content::type('CustomerGroup', $this->project)
                )->field(
                    ['name' => fn ($item) => 'Kundenpreisregel ' .  $item->properties('name') . ' | ' . $item->unique_id],
                ),
            Set::upload('CustomerGroups')
                ->items(
                    fn () => Content::type('CustomerGroup', $this->project)
                        ->whereNot('unique_id', 'EK')
                )->fields([
                    ['name' => fn ($item) => 'Kundengruppe ' . $item->properties('name') . ' | ' . $item->unique_id],
                    $this->setShopwareIdField()
                ]),
        ];
    }
}
