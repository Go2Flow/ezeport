<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Models\Project;
use ArrayAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Config extends Base implements ArrayAccess {

    public mixed $config;
    public ?Collection $path = null;

    protected function getObject(Project $project, ?string $path = null ) : self
    {
        $this->config = file_exists($this->filePath($project->identifier, 'config.php'))
            ? include($this->filePath($project->identifier, 'config.php'))
            : [];

        $this->setPath($path);

        return $this;
    }

    public function find(?string $path = null)
    {
        $this->setPath($path);

        foreach ($this->path as $item) {
            if ($this->offsetExists($item)) $this->config = $this->offsetGet($item);
            else return null;
        }

        return $this->config;
    }

    private function setPath(?string $path = null): void
    {
        if ($path) $this->path = Str::of($path)->explode('.');
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
