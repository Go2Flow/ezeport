<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Models\Project;

abstract class Base {

    CONST CUSTOMER_PATH = 'App\\Ezport\\Customers\\';

    protected $object;


    public function __construct(array $identifier)
    {
        $this->object = $this->getObject(... $identifier);
    }

    public function __call($method, $args)
    {
        if (method_exists($this, $method)) return $this->$method(...$args);

        return $this->object->$method(...$args);
    }

    protected function mergeConfigWithStandard(Project $project, string $config, string $standard) : array
    {
        return array_merge(Find::config($project)[$config] ?? [], [$standard]);
    }

    protected function instructionPath($identifier, ?string $string) : string
    {
        $path = self::CUSTOMER_PATH . ucfirst($identifier) .  '\Instructions\\';

        if ($string) $path .= ucfirst($string);
        return $path;
    }
}
