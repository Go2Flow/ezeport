<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Illuminate\Support\Collection;

class Jobs extends Base{

    protected ?collection $steps;
    protected string $type;
    protected collection $environment;

    public function __construct(string $key = '')
    {
        $this->environment = collect();
        $this->key = $this->processKey($key);
        $this->steps = collect();
    }

    public function upload() : self {

        return $this->setKey('upload');
    }

    public function shopClean() : self {

        return $this->setKey('shopClean');
    }

    public function ftpClean() : self {

        return $this->setKey('ftpClean');
    }

    public function clean() : self {
        return $this->setKey('clean');
    }

    public function import() : self {

        return $this->setKey('import');
    }

    public function transform() : self {

        return $this->setKey('transform');
    }

    /**
     * @deprecated
     */

    public function partial() : self {

        $this->type = 'partial';

        return $this;
    }

    /**
     * @deprecated
     */
    public function full() : self {

        $this->type = 'full';

        return $this;
    }

    public function type(string $string) : self {

        $this->type = $string;

        return $this;
    }

    public function production() : self {

        return $this->setEnvironment('production');
    }

    public function staging() : self {

        return $this->setEnvironment('staging');
    }

    public function local() : self
    {
        return $this->setEnvironment('local');
    }

    public function development() : self
    {
        return $this->setEnvironment('development');
    }

    public function testing() : self
    {
        return $this->setEnvironment('testing');
    }

    private function setEnvironment(string $environment) : self
    {
        $this->environment->push($environment);

        return $this;
    }

    /**
     * a step sets is a collection of jobs
     * they will be worked through in order with one step being completed before the next
     */

    public function step(Step|array|Collection $content, string $name = '') : self
    {
        $this->steps->push(
            $content instanceof Step
                ? $content
                : new Step($name, $content)
        );

        return $this;
    }

    public function getJobs() : Collection
    {
        return $this->steps;
    }

    private function setKey(string $key) : self
    {
        $this->key = $this->processKey($key);

        return $this;
    }

    public function correctEnv() : bool
    {
        return $this->environment->contains(config('app.env'));
    }
}
