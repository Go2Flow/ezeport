<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Closure;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Getters\GetProxy;
use Go2Flow\Ezport\Instructions\Interfaces\ImportInstructionInterface;
use Go2Flow\Ezport\Process\Jobs\AssignShopImport;
use Illuminate\Support\Collection;

class ShopImport extends Basic implements ImportInstructionInterface {

    protected string $type;
    protected string $unique_id;
    protected closure $items;
    protected closure $process;
    protected Collection $properties;
    protected Collection $shopware;
    protected GetProxy $api;

    public function __construct(string $key, private array $config = [])
    {
        $this->key = $this->processKey($key);
        $this->type = $this->key;

        $this->properties = collect();
        $this->shopware = collect();

        $this->items = fn () => null;
        $this->process = fn () => null;

        $this->jobClass = AssignShopImport::class;
    }

    /**
     * Set the Api. Use the Find static method or Api class.
     */

     public function api(GetProxy|Api|string $api) : self {

        $this->api = (is_string($api))
            ? Get::api($api)
            : $api;

        return $this;
    }

    /**
     * get all the items that need to be imported. The closure should return a collection of ids.
     * These will then be individually called and processed in the 'process' closure.
     * If you don't call this method then the process closure will be called with an empty collection.
     */

    public function items(Closure $items) : self
    {
        return $this->setClosure('items', $items);
    }

    /**
     * The process closure expects a collection of ids from the items closure.
     * These will already be chunked before they're passed to the closure so you don't need to do that.
     */

    public function process(Closure $process) : self
    {
        return $this->setClosure('process', $process);
    }

    public function properties(Closure $properties) : self
    {
        return $this->pushToCollection('properties', $properties);
    }

    public function shopware(Closure $shopware) : self
    {
        return $this->pushToCollection('shopware', $shopware);
    }

    public function type(string $type) : self
    {
        return $this->setString('type', $type);
    }

    public function uniqueId(string $uniqueId) : self
    {
        return $this->setString('uniqueId', $uniqueId);
    }

    private function setClosure(string $type, Closure $closure) : self
    {
        $this->$type = $closure;

        return $this;
    }

    private function pushToCollection(string $type, Closure $closure) : self
    {
        $this->$type->push($closure);

        return $this;
    }

    private function setString(string $type, string $string)
    {
        $this->$type = $string;

        return $this;
    }
}
