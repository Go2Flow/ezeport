<?php

namespace Go2Flow\Ezport\Instructions\Setters;

use Closure;
use Go2Flow\Ezport\Instructions\Interfaces\ImportInstructionInterface;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Process\Jobs\AssignXml;
use Illuminate\Support\Collection;

class XmlImport extends Basic implements JobInterface, ImportInstructionInterface {

    protected ?array $path = null;
    protected ?string $type = null;
    protected Collection $values;
    protected Collection $updateIf;
    protected Collection $attributes;
    protected Collection $arrays;
    protected Collection $components;
    protected ?closure $closure = null;
    protected $setStandardFields = ['path', 'type'];
    protected $setSpecialFields = ['values', 'updateIf', 'attributes', 'arrays', 'components'];

    public function __construct(string $key, array $config = [])
    {
        parent::__construct($key);

        foreach ($this->setSpecialFields as $key) {
            $this->{$key} = collect($config[$key] ?? []);
        }

        foreach ($this->setStandardFields as $key) {
            $this->{$key} = $config[$key] ?? null;
        }

        $this->jobClass = AssignXml::class;

        $this->type = $this->key->singular()->camel()->ucFirst()->toString();
    }

    public function arrays(array|Set|XmlImport $content) : self
    {
        return $this->newSet('arrays', $content);
    }

    public function components(array|Set|XmlImport $content) : self
    {
        return $this->newSet('components', $content);
    }

    public function closure(closure $closure) : self {

        $this->closure = $closure;

        return $this;
    }

    private function newSet($name, $content)
    {
        $this->$name->push(
            $content instanceof XmlImport
                ? $content
                : new XmlImport($key = collect($content)->keys()[0], $content[$key])
        );

        return $this;
    }

    /**
     * @method self values(array $content)
     * @method self attributes(array $content)
     * @method self updateIf(array $content)
     * @method self path(array $path)
     */

    public function __call($method, $content)
    {
        if (method_exists($this, $method)) return $this->$method(...$content);

        if (in_array($method, $this->setSpecialFields))
        {
            $this->{$method} = $this->$method->merge($content[0]);

            return $this;
        }

        if (in_array($method, $this->setStandardFields))
        {
            $this->$method = $content[0];

            return $this;
        }

        throw new \Exception("Method {$method} does not exist in " . __CLASS__);
    }

    protected function setSpecificFields() : array
    {
        return [
            'action' => $this->key->toString(),
        ];
    }

}
