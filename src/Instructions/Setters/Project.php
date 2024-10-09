<?php

namespace Go2Flow\Ezport\Instructions\Setters;

class Project extends Base{

    protected ?string $name;
    protected ?array $settings;
    protected ?array $cache;

    /**
     * use the same key as you've specified when you set up the folder structure
    */

    public function __construct(string $key, array $config = [])
    {
        $this->key = $this->processKey($key);

        foreach (['name', 'settings', 'cache'] as $key)
        {
            $this->{$key} = $config[$key] ?? null;
        }
    }

    /**
     * set the name of the project
    */

    public function name(string $string) : self
    {
        $this->name = $string;

        return $this;
    }

    /**
     * set the settings of the project. This is mainly used to set up the cache when preparing the project.
    */

    public function settings(array $array) : self
    {
        $this->settings = $array;

        return $this;
    }

    /**
     * set the cache of the project. This is where project ids and such are stored for easy retrieval
    */

    public function cache(array $array) : self
    {
        $this->cache = $array;

        return $this;
    }
}
