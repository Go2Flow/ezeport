<?php

namespace Go2Flow\Ezport\Instructions\Setters\Interfaces;

use Illuminate\Contracts\Queue\ShouldQueue;

interface JobInterface {

    public function getJob(array $content = []) : ShouldQueue;
}
