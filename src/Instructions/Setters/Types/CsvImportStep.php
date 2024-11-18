<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Closure;

class CsvImportStep extends Base {

    protected string $file = '';
    protected bool $create = false;
    protected Closure $structure;
    protected int $stage = 0;
    protected array $config = [];

    /**
     * set the file to import
     */

    public function file(string $string)  : self
    {
        $this->file = $string;

        return $this;
    }

    public function config(array $config) : self{

        $this->config = $config;

        return $this;
    }

    /**
     * This will create a new contentType (as specified in the CsvImport) and place the imported data in it.
     */

    public function create() : self
    {
        $this->create = true;

        return $this;
    }

    /**
     * The transform method needs a closure. The paramater is the untransformed data as a Collection.
     * The closure should return an Collection with the transformed data.
     */

    public function transform(closure $closure) : self
    {
        $this->structure = $closure;

        return $this;
    }

    public function stage(int $stage) : self
    {
        $this->stage = $stage;

        return $this;
    }
}
