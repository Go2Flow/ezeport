<?php

namespace Go2Flow\Ezport\Process\Import;

use Closure;

interface ImportInterface{

    public function process(Closure $type);
}
