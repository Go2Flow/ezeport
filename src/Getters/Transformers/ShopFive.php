<?php

namespace Go2Flow\Ezport\Getters\Transformers;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Str;

class ShopFive extends BaseInstructions implements InstructionInterface {

    public function get() : array
    {
        return [
            Set::Transform('Categories')
                ->prepare(
                    fn (): Builder => Content::type('Category', $this->project)
                )->process(
                    function ($category) {

                        $category->properties('name') === 'Root'
                            ? $category->delete()
                            : $category->relationsAndSave();
                    }
                ),
            Set::Transform('Articles')
                ->prepare(
                    fn (): Builder => Content::type('Article', $this->project)
                )->process(
                    function ($item, $config) {

                        if (! isset($config['properties']) ||  ! $properties = $item->properties('properties')) return;

                        $relations = collect();

                        foreach ($properties as $content)
                        {
                            $property = new Generic ([
                                'unique_id' => Str::of($content['text'])->slug()->lower()->toString(),
                                'project_id' => $this->project->id,
                                'type' => $config['properties'][$content['optionId']]['class']
                            ]);

                            $property->properties($content);
                            $property->updateOrCreate(true);

                            if (!isset($relations[$config['properties'][$content['optionId']]['type']])) {
                                $relations[$config['properties'][$content['optionId']]['type']] = collect();
                            }

                            $relations[$config['properties'][$content['optionId']]['type']]->push($property);
                        }

                        if ($relations->isNotEmpty() && $relations->count() > 0) {
                            $item->relations($relations);
                            $item->relationsAndSave(true);
                        }
                    }
                ),
        ];
    }
}
