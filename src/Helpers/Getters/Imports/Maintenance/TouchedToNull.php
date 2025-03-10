<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\Maintenance;


use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Models\GenericModel;

class TouchedToNull  extends BaseInstructions implements InstructionInterface {


    public function get() : array
    {
        return [
            Set::model('TouchedToNull')
                ->items(fn () => GenericModel::where('project_id', $this->project->id)
                    ->where('touched', true)
                    ->pluck('id'))
                ->instructions([
                    'action' => ['touched' => false], 'method' => 'update'
                ]),
        ];
    }
}
