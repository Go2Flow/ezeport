<?php

namespace Go2Flow\Ezport\Import\Helpers;

use Go2Flow\Ezport\Finders\Find;

trait HasStructure {

    protected function structuresFromFile()
    {
        return Find::instrution($this->project, 'Import')->get();
    }
}
