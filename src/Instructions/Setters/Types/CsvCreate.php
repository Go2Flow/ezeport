<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

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
