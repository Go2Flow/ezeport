<?php

namespace Go2Flow\Ezport\Finders\Interfaces;

interface InstructionInterface
{
    public function get();
    public function find(string $string);
}
