<?php

namespace Go2Flow\Ezport\Instructions\Setters\Special;
use Closure;
use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Helpers\Traits\Uploads\ArticleFields;
use Go2Flow\Ezport\Helpers\Traits\Uploads\FieldHelpers;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\UploadFieldInterface;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadField;
use Illuminate\Support\Str;

class PriceField extends UploadField implements UploadFieldInterface {

    use FieldHelpers, ArticleFields;

    private ?Closure $price;
    private ?Closure $discount = null;
    private string $addOrRemove = 'add';
    private ?float $tax = null;

    public function __construct(string $key = null)
    {
        if ($key == null) {
            $key = 'Price';
        }

        parent::__construct($key);
    }

    public function price(Closure $price) : self
    {
        $this->price = $price;

        return $this;
    }


    public function discount (Closure $discount) : self
    {
        $this->discount = $discount;

        return $this;
    }

    public function tax (float $tax) : self {

        $this->tax = $tax;

        return $this;

    }

    public function add()  : self
    {
        $this->addOrRemove = 'add';

        return $this;
    }

    public function remove() : self
    {
        $this->addOrRemove = 'remove';

        return $this;
    }

    public function gross() : self
    {
        return $this->remove();
    }

    public function net() : self
    {
        return $this->add();
    }

    public function process(Generic $item, array $config) : array {

        return [
            'key' => $this->key?->toString(),
            'value' => $this->calculatePriceWithDiscount(
                ($this->price)($item),
                $this->discount ? ($this->discount)($item) : null,
                $this->addOrRemove,
                $this->tax
            )
        ];
    }
}
