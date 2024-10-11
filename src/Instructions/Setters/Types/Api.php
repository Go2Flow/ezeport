<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Illuminate\Support\Collection;

class Api extends Base {

    protected Collection $config;

    public function __construct(string $key, array|Collection $config = [])
    {
        $this->key = $this->processKey($key);
        $this->config = collect($config);
    }
    /**
     * set the names to folders to standardize the ftp calls
     */

    public function paths(array|Collection $paths) : self
    {
        $this->config = collect($paths);

        return $this;
    }

    public function get(string $key, bool $flip = false) : ?string
    {
        if ($flip) return $this->config->flip()->get($key);

        return $this->config->get($key);
    }

    public function getConfig() : Collection
    {
        return $this->config;
    }
}

