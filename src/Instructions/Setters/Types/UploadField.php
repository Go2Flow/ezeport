<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Closure;
use Go2Flow\Ezport\ContentTypes\Generic;
use Illuminate\Support\Str;

class UploadField extends Base {

    private ?closure $value;
    private ?Upload $component = null;

    public function __construct(string $key = null)
    {
        $this->key = $key ? Str::of($key) : null;
    }

    /**
     * If the value is not a closure it will be turned into one.
     * All closures will be called with the item and config as arguments.
     * You can add something to the config to use in later fields by returning an array with the keys 'array' and 'config'.
     * What is in the 'array' key will be added to the upload array.
     * What is in the 'config' key will be merged into the config attribute and passed into all subsequent UploadFields.
     */

    public function field(mixed $value) : self {

        $this->value = $value instanceof Closure
            ? $value
            : fn () => $value;

        return $this;
    }

    /** will process the field for the actual upload. */

    public function process(Generic $item, array $config) : array|null {

        $value = ($this->value)($item, $config, $this->component);

        if ((is_array($value) && count($value) === 0) || ($value === null)) return null;

        return [
            'key' => $this->key ? $this->key->toString() : null,
            'value' => $value
        ];
    }
}
