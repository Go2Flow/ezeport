<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Models\Project;
use ArrayAccess;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class Config extends Base implements ArrayAccess {

    public mixed $config;
    public ?Collection $path = null;

    protected function getObject(Project $project, ?string $path = null): self
    {
        $id = $project->id;
        $identifier = $project->identifier;
        $fullPath = $this->filePath($identifier, 'config.php');

        Log::info('EZPORT cfg load', [
            'project_id' => $id,
            'identifier_hex' => bin2hex($identifier), // catches hidden chars
            'full_path' => $fullPath,
            'exists' => file_exists($fullPath),
            'cwd' => getcwd(),
            'app_path' => base_path(),
            'mtime' => @filemtime($fullPath) ?: null,
            'sha1' => @sha1_file($fullPath) ?: null,
        ]);

        $loaded = file_exists($fullPath) ? include $fullPath : [];
        $this->config = is_array($loaded) ? $loaded : [];

        Log::info('EZPORT cfg keys', [
            'keys' => array_keys($this->config),
            'count' => count($this->config),
        ]);

        return $this;
    }

    public function find(?string $path = null)
    {
        $this->setPath($path);

        $config = $this->config;

        foreach ($this->path as $item) {
            if (!is_array($config) || !array_key_exists($item, $config)) {
                return null;
            }

            $config = $config[$item];
        }

        return $config;
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
