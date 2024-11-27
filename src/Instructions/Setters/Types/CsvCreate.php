<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Upload\Csv\Creates\Create;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;

class CsvCreate extends Upload {

    private string $file;
    protected bool $showNull = true;

    protected \Closure|null $process;

    public function pluck(): Collection
    {
        $response = $this->builder();

        if (!$response instanceof Builder) return collect([$response]);

        return collect([
            $response->whereUpdated(true)
            ->whereTouched(true)
            ->pluck('id')
        ]);
    }
}
