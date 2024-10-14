<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Process\Errors\EzportSetterException;

/**
 * @method username(string $string)
 * @method host(string $string)
 * @method password(string $string)
 * @method environment(string $string)
 */


class Connector extends Base {

    protected ?string $name;
    protected ?string $password;
    protected ?string $host;
    protected ?string $environment;
    protected $fields = ['username', 'password', 'host'];

    public function __construct(string $key, array $config = [])
    {
        $this->key = $this->processKey($key);

        foreach ($this->fields as $key) $this->{$key} = $config[$key] ?? null;
    }

    public function production() : self
    {
        return $this->setEnvironment('production');
    }

    public function staging() : self
    {
        return $this->setEnvironment('staging');
    }

    public function local() : self
    {
        return $this->setEnvironment('local');
    }

    public function __call($method, $arguments) : self
    {
        if (method_exists($this, $method)) return $this->$method(...$arguments);

        if (in_array($method, $this->fields))
        {
            if (! is_string($arguments[0])) throw new EzportSetterException("Method {$method} must be a string in " . __CLASS__);

            $this->$method = $arguments[0];

            return $this;
        }

        throw new EzportSetterException("Method {$method} does not exist in " . __CLASS__);
    }

    private function setEnvironment(string $string) : self {
        $this->environment = $string;

        return $this;
    }
}
