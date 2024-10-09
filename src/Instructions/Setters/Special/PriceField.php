<?php

namespace Go2Flow\Ezport\Instructions\Setters\Special;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Instructions\Helpers\Uploads\ArticleFields;
use Go2Flow\Ezport\Instructions\Helpers\Uploads\FieldHelpers;
use Go2Flow\Ezport\Instructions\Setters\UploadField;
use Closure;
use Illuminate\Support\Str;

class PriceField extends UploadField {

    use FieldHelpers, ArticleFields;

    private ?Closure $price;
    private ?Closure $discount = null;
    private string $addOrRemove = 'add';

    public function __construct(string $key = null)
    {
        if ($key == null) {
            $key = 'Price';
        }

        $this->key = Str::of($key);
    }

    public function price(Closure $price) {
        $this->price = $price;

        return $this;
    }


    public function discount (Closure $discount) {
        $this->discount = $discount;

        return $this;
    }

    public function add()
    {
        $this->addOrRemove = 'add';

        return $this;
    }

    public function remove()
    {
        $this->addOrRemove = 'remove';

        return $this;
    }

    public function gross()
    {
        $this->addOrRemove = 'remove';

        return $this;
    }

    public function net()
    {
        $this->addOrRemove = 'add';

        return $this;
    }

    public function process(Generic $item, array $config) : array|null {

        return [
            'key' => $this->key ? $this->key->toString() : null,
            'value' => $this->calculatePriceWithDiscount(
                ($this->price)($item),
                $this->discount ? ($this->discount)($item) : null,
                $this->addOrRemove,
            )
        ];
    }
}
