<?php

namespace Go2Flow\Ezport\Import\Csv;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Instructions\Setters\CsvImport;
use Illuminate\Support\Collection;

class Transformer {

    private Collection $items;

    public function __construct(private CsvImport $instructions ) {}

    public function setItems(Collection $items) : self {

        $this->items = $items;

        return $this;
    }

    public function process() : void
    {
        if ($prepare = $this->instructions->get('prepare')) $this->items = $prepare($this->items);

        $this->items = $this->items->map(
            fn ($item) => $item instanceof Generic ? [$item] : $item
        );

        if (! $process = $this->instructions->get('process')) {
            $process = fn ($item) => $item->relationsAndSave(true);
        }

        $this->items->each(fn ($item) => $process(... $item));
    }
}
