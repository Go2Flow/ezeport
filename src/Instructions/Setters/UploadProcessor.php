<?php

namespace Go2Flow\Ezport\Instructions\Setters;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Getters\GetProxy;
use Closure;
use Illuminate\Support\Collection;

class UploadProcessor extends Base {

    protected ?closure $process;
    protected ?GetProxy $api = null;
    protected array $config = [];
    protected array $components = [];

    public function __construct(?string $key = null) {

        if ($key) $this->key = $this->processKey($key);
    }

    /**
     * Set how the items should be uploaded. This closure will be passed the items and the api.
     */

    public function process(Closure $process) : self {

        $this->process = $process;

        return $this;
    }

    public function config(array $config) : self {

        $this->config = $config;

        return $this;
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

    public function setComponents(array $components) : self {

        $this->components = $components;

        return $this;
    }

    public function run(Collection $items) {

        ($this->process)(
            $items,
            $this->getOrSetApi()($this->project),
            $this->config,
            $this->components
        );
    }

    private function getOrSetApi()
    {

        return ($this->api ?? Get::api(Find::config($this->project)['api'] ?? 'ShopSix'));

    }
}
