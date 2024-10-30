<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Constants\Paths;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Str;

abstract class Base {
//    CONST CUSTOMER_PATH = 'App\\Ezport\\Customers\\';

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

    protected function filePath(string $identifier, ?string $string) : string
    {
        return Paths::appCustomers() . ucfirst($identifier) . '/' . $string;
    }

    protected function instructionPath(string $identifier, ?string $string) : string
    {

        $path = Str::of(Paths::appCustomers())->replace("/", "\\", )->ucfirst() . ucfirst($identifier) .  '\Instructions\\';

        return ($string)
            ? $path . ucfirst($string)
            : $path;
    }
}
