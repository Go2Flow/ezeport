<?php

namespace Go2Flow\Ezport\Helpers\Getters\Uploads\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\GeneralFields;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Models\GenericModel;

class Categories extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers,
        StandardShopSixArticle,
        ArticleFields,
        GeneralFields;

    public function get() : array
    {
        return [
            Set::Upload('categories')
                ->items(
                    function () {
                        $assigned = collect();
                        $models = GenericModel::whereType('Category')
                            ->whereProjectId($this->project->id)
                            ->whereUpdated(true)
                            ->doesntHave('children')
                            ->get();

                        while ($models->count()) {
                            $assigned->push(...$models->pluck('id'));

                            $models = $models->flatMap(
                                fn ($item) => $item->parents()->whereType('Category')->whereProjectId($this->project->id)->get()
                            )->filter();
                        }

                        return $assigned;
                    }
                )->fields([
                    ['type' => 'page'],
                    ['productAssignmentType' => 'product'],
                    ['displayNestedProducts' => true],
                    ['name' => fn ($item) => $item->name ],
                    $this->setCategoryCmsPageIdField(),
                    $this->setShopwareIdField(),
                    Set::uploadField('parentId')
                        ->field(
                            function ($item) {
                                if ($parent = $item->relations('category')) {
                                    return $parent->shopware('id');
                                }
                                return $this->project->cache('category_ids')['parent'];
                            }
                        )
                ]),
        ];
    }
}

