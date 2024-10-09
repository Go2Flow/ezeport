<?php

namespace Go2Flow\Ezport\Import;

use Closure;

interface ImportInterface{

    public function process(Closure $type);
}
