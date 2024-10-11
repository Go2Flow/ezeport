<?php

namespace Go2Flow\Ezport\Finders\Abstracts;

use Go2Flow\Ezport\Instructions\Getters\GetProxy;
use Go2Flow\Ezport\Instructions\Setters\Types\Base;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

abstract class BaseInstructions
{
    public function __construct(protected Project $project)
    {
    }

    public function find(string $string) : ?Base
    {
        return $this->byKey($string);
    }

    public function findAll(string $string)
    {
        return $this->collect()->filter(
            fn ($item) => $item->hasKey($string)
        );
    }

    public function getAndSet(): Collection
    {
        return $this->collect()->map(
            fn ($item) => $item->setProject($this->project)
        );
    }

    public function collect(): Collection
    {
        return collect($this->get())
            ->map(
                fn ($item) => ($item instanceof GetProxy
                    ? $item($this->project)
                    : $item
                )->setProject($this->project)
            );
    }

    public function byKey(string|Stringable $key): Base|null
    {
        return $this->collect()
            ->filter(
                fn ($item) => $item->hasKey(
                    $key instanceof Stringable
                        ? $key->__toString()
                        : $key
                )
            )->first();
    }

    public function get() : array
    {
        return [];
    }

    public function __call($method, $args)
    {
        if (method_exists($this, $method)) return $this->$method(...$args);

        if (Str::contains($method, 'get')) {

            $attribute = Str::of($method)->after('get');
            if (property_exists($this, $attribute)) {
                return $this->$attribute;
            }
        }

        throw new \BadMethodCallException("Method {$method} does not exist on this object");
    }
}
