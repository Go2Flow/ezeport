<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Models\Project;
use ArrayAccess;

class Config extends Base implements ArrayAccess {

    public array $config;

    protected function getObject(Project $project) : self
    {
        $this->config = file_exists($this->instructionPath($project->identifier, 'config.php'))
            ? include($this->instructionPath($project->identifier, 'config.php'))
            : [];

        return $this;
    }

    public function offsetExists($offset) : bool
    {
        return isset($this->config[$offset]);
    }

    public function offsetGet($offset) : mixed
    {
        return $this->config[$offset];
    }

    public function offsetSet($offset, $value) : void
    {
        $this->config[$offset] = $value;
    }

    public function offsetUnset($offset) : void
    {
        unset($this->config[$offset]);
    }
}
