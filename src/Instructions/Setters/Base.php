<?php

namespace Go2Flow\Ezport\Instructions\Setters;

use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

abstract class Base {

    protected ?Stringable $key;
    protected ?Project $project;

    public function setProject(Project $project) : self
    {
        $this->project = $project;

        return $this;
    }

    /** change the key */

    public function key(string $key): self
    {
        $this->key = $this->processKey($key);

        return $this;
    }

    public function hasKey(string|Stringable $key): bool
    {
        return $this->key == $this->processKey($key);
    }

    public function getKey(): ?string
    {
        return $this->key ? $this->key->__toString() : null;
    }

    public function get(string $key)
    {
        if (! property_exists($this, $key)) throw new \Exception("Attribute {$key} does not exist in " . __CLASS__);

        return $this->$key;
    }

    public function getThis() : self
    {
        return $this;
    }

    protected function processKey(string|Stringable $key) : Stringable
    {
        $plural = Str::lower($key) === 'media' ? true : false;

        return Str::of($key)
            ->ucfirst()
            ->when(
                $plural,
                fn ($key) => $key->plural(),
                fn ($key) => $key->singular()
            )->camel();
    }

    public function __call($method, $arguments)
    {
        if (Str::startsWith($method, 'get')) {

            $key = Str::of($method)->after('get')->camel();

            return $this->get($key);
        }

        throw new \BadMethodCallException("Method {$method} does not exist on this object");
    }
}
