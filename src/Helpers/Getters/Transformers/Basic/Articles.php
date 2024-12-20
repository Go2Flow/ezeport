<?php

namespace Go2Flow\Ezport\Helpers\Getters\Transformers\Basic;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;

class Articles extends BaseInstructions implements InstructionInterface {


    public function get() : array
    {
        return [
            Set::Transform('Articles')
                ->prepare(
                    function () {
                        $ids = collect();

                        Content::type('Article', $this->project)
                            ->whereTouched(true)
                            ->whereUpdated(true)
                            ->chunk(
                                100,
                                function ($articles) use (&$ids) {
                                    $ids->push($articles->pluck('id'));
                                }
                            );

                        return $ids->flatten();
                    }
                ),
        ];
    }
}
