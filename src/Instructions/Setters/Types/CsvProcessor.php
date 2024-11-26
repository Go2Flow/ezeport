<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Process\Upload\Csv\Creates\Create;
use Illuminate\Support\Collection;

class CsvProcessor extends UploadProcessor {

    public function run(Collection $items) {

        ($this->process)(
            $items,
            Create::class,
            $this->config,
            $this->components
        );
    }
}
