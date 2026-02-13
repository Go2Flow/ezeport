<?php

namespace Go2Flow\Ezport\Instructions\Setters\Interfaces;

use Illuminate\Support\Collection;

interface Assignable {

    public function assignJobs(): Collection;
}