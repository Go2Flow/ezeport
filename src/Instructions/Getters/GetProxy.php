<?php

namespace Go2Flow\Ezport\Instructions\Getters;

class GetProxy {

    private $callback;
    private array $methods = [];

    public function __construct(callable $callback) {

        $this->callback = $callback;
    }

    public function __call($method, $arguments)
    {
        $this->methods[] = compact('method', 'arguments');
        return $this;
    }

    public function __invoke($project) {


        $object = ($this->callback)($project);

        foreach ($this->methods as $method) $object->{$method['method']}(...$method['arguments']);

        return $object;
    }
}
