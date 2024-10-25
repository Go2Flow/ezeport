<?php

namespace Go2Flow\Ezport\Instructions\Setters\Special;

use Go2flow\Ezport\ContentTypes\Generic;
use Closure;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadField;
use Illuminate\Support\Str;

class PricesField extends UploadField {

    private ?closure $prices;


    public function __construct(string $key = null)
    {
        if ($key == null) {
            $key = 'prices';
        }

        $this->key = Str::of($key);
    }

    public function prices (?closure $prices) : self
    {
        $this->prices = $prices;
        return $this;
    }

    public function process(Generic $item, array $config = []) : array|null {

        return [
            'key' => 'prices',
            'value' => collect(($this->prices)($item, $config))->map(
                fn ($price) => collect($price)->mapWithKeys(
                    function ($line, $key) use ($item, $config)  {
                        if (! $line instanceof PriceField) return [$key => $line];

                        $response = $line->setProject($this->project)->process($item, $config);

                        return [$response['key'] => $response['value']];
                    }
                )
            )->toArray()
        ];
    }
}
