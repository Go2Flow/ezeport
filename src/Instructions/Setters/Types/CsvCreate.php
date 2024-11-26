<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Jobs\AssignCreateCsv;
use Go2Flow\Ezport\Process\Upload\Csv\Creates\Create;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;

class CsvCreate extends Upload {

    private string $file;

    protected \Closure|null $process;

    public function pluck(): Collection
    {
        $response = $this->builder();

        if (!$response instanceof Builder) return $response;

        return $response->whereUpdated(true)
            ->whereTouched(true)
            ->pluck('id');
    }
}
