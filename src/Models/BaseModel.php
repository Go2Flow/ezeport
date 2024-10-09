<?php

namespace Go2Flow\Ezport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BaseModel extends Model {

    public function getOrSetData(mixed $input, string $property)
    {
        if (! $input) return collect($this->$property);

        if (is_string($input)) return $this->getByInput($input, $property);

        if (! $this->$property) return $this->$property = collect($input);

        return $this->$property = $this->$property->merge($input);
    }

    private function getByInput(string $input, string $property)
    {
        if (isset($this->$property[$input]) ) {
            $response = $this->$property[$input];
            return is_array($response)
                ? collect($response)
                : $response;
        }

        if (isset(($this->$property[Str::plural($input)]) )) {
            $response = $this->$property[Str::plural($input)];
            if (is_array($response)) return reset($response);
            if ($response instanceof Collection) return $response->first();

        }

        return null;
    }
}
