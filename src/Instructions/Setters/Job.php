<?php

namespace Go2Flow\Ezport\Instructions\Setters;

class Job extends Base{

    protected ?array $config = [];
    protected ?string $class = null;

    public function __construct(array $config = []){

        if (isset($config['class']) && is_string($config['class'])) $this->class = $config['class'];
        if (isset($config['config']) && is_array($config['config'])) $this->config = $config['config'];
    }


    /**
     * set the class of the job that will be run
     */

    public function class(string $class) : self
    {
        $this->class = $class;
        return $this;
    }

    /**
     * set the instructions to be passed into the job. These will be available as the 2nd parameter in the job's constructor
     * (The first parameter is reserved for the Project)
     */

    public function config(array $config) : self
    {
        $this->config = $config;

        return $this;
    }

    public function getJob() : string
    {
        return $this->class;
    }

    public function getConfig() : array
    {
        return $this->config;
    }
}
