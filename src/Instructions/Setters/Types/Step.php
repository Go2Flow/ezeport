<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Illuminate\Support\Collection;

class Step extends Basic implements JobInterface {

    private Collection $content;

    public function __construct(string $key, array $config = []){

        parent::__construct($key);
        $this->content = collect($config);
    }

    public function content(array|Collection $content) : self
    {
        $this->content = collect($content);

        return $this;
    }

    public function getContent() : Collection
    {
        return $this->content;
    }
}
