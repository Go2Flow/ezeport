<?php

namespace Go2Flow\Ezport\Helpers\Getters\Uploads\ShopwareFiveToSix;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Setters\Set;

class CrossSellings extends BaseInstructions implements InstructionInterface {

    use GeneralHelpers,
        ArticleFields,
        GeneralFields;

    public function get() : array{

        return [
            Set::Upload('CrossSellings')
                ->items(fn () => Content::type('Article', $this->project))
                ->field(
                    Set::UploadField()
                        ->field(
                            function ($item) {

                                $ids = $item->properties('related')
                                    ?->map(fn ($item) => Content::type('Article', $this->project)->find($item)?->shopware('id'))
                                    ->filter()
                                    ->values();

                                if (! $ids ||  $ids->count() == 0) return [];

                                return [
                                    'productId' => $item->shopware('id'),
                                    'name' => 'Modellfamilie',
                                    'position' => 1,
                                    'type' => 'productList',
                                    'active' => true,
                                    'assignedProducts' => $ids->map(fn ($selling, $key) => [
                                        'productId' => (string) $selling,
                                        'position' => $key + 1,
                                    ])->toArray()
                                ];
                            }
                        )
                ),
        ];
    }
}

