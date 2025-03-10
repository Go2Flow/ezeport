<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\Maintenance;


use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Models\GenericModel;

class RemoveUntouched  extends BaseInstructions implements InstructionInterface {


    public function get() : array
    {
        return [
            Set::model('RemoveUntouched')
                ->items(fn () => GenericModel::where('project_id', $this->project->id)
                    ->where('touched', false)
                    ->where('updated', false)
                    ->pluck('id'))
                ->instructions([
                    'method' => 'delete'
                ]),
        ];
    }
}
