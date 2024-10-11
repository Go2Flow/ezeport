<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Illuminate\Contracts\Queue\ShouldQueue;

class Basic extends Base implements JobInterface {

    protected ?Job $job;
    protected ?string $jobClass = null;

    public function __construct(string $key){

        $this->key = $this->processKey($key);
    }


    public function job(Job $job): self{

        $this->job = ($this->jobClass && ! $job->getClass())
            ? $job->class($this->jobClass)
            : $job;

        return $this;
    }

    public function getJob(array $array = []) : ShouldQueue
    {
        return new ($this->job->getJob())(
            $this->project->id,
            array_merge(
                $this->job->getConfig(),
                $array,
                $this->setSpecificFields(),
                ['key' => $this->key]
            )
        );
    }

    public function getJobConfig() : array
    {
        return $this->job->getConfig();
    }

    protected function setSpecificFields() : array
    {
        return [];
    }
}
