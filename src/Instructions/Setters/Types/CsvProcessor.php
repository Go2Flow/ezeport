<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Process\Batches\Tools\SmallestQueue;
use Go2Flow\Ezport\Process\Upload\Csv\Creates\Create;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CsvProcessor extends UploadProcessor {

    private string $file;

    public function file(string $file) : self
    {
        $this->file = $file;

        return $this;
    }

    public function run(Collection $items) : void {

        (new Create(collect($items->map->toShopArray()), $this->config))
            ->queue(Str::ucfirst($this->project->identifier) . '/' . $this->file, 'public')
            ->allOnQueue(SmallestQueue::get());
    }
}
