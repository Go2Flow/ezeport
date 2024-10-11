<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Process\Jobs\AssignClean;
use Illuminate\Support\Collection;

class FtpCleaner extends Basic implements JobInterface {

    private ?string $class;
    private ?array $config;
    private $cleaner = null;

    public function __construct(string $key)
    {
        $this->key = $this->processKey($key);

        $this->job = Set::Job()
            ->class(AssignClean::class);
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
     * set the instructions to be passed into the job
     */

    public function config(array $config) : self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * sets up the cleaner job
     */

    public function getCleaner() : self
    {
        $this->cleaner =  new ($this->class)(
            $this->project->id,
            $this->config,
        );

        return $this;
    }

    public function prepareItems() : self
    {
        return $this;
    }

    /**
     * returns the cleaner job as an item in a collection
     */

    public function preparejobs() : Collection {

        return collect([$this->cleaner]);
    }
}
