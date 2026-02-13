<?php

namespace Go2Flow\Ezport\Instructions\Setters\Interfaces;

interface Executable {

    public function execute(array $config): void;
}