<?php

namespace Go2Flow\Ezport\Instructions\Setters\Interfaces;

use Go2Flow\Ezport\ContentTypes\Generic;

interface UploadFieldInterface {

    public function process(Generic $item, array $config) : array|null;
}
