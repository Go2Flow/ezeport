<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Constants\Paths;

abstract class Base {

    protected $object;

    public function __construct(array $arguments)
    {
        $this->object = $this->getObject(... $arguments);
    }

    public function __call($method, $args)
    {
        if (method_exists($this, $method)) return $this->$method(...$args);

        return $this->object->$method(...$args);
    }

    protected function instructionFilePath(string $identifier, ?string $string) : string
    {
        return Paths::filePath(ucfirst($identifier), 'Instructions', ucfirst($string) .'.php');
    }
    protected function filePath(string $identifier, ?string $string) : string
    {
        return Paths::filePath(ucfirst($identifier), $string .'.php');
    }

    protected function instructionPath(string $identifier, ?string $string) : string
    {
        $path = Paths::className( ucfirst($identifier),  'Instructions');

        return ($string)
            ? $path . '\\' . ucfirst($string)
            : $path;
    }
}
