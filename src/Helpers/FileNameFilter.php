<?php

namespace Go2Flow\Ezport\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Selects server files by their base name, as configured on file jobs:
 *  - 'not'         => exact base names to leave out
 *  - 'contains'    => keep only names containing one of these strings
 *  - 'notContains' => leave out names containing one of these strings
 *  - 'type'        => keep only names ending with this suffix
 */
class FileNameFilter
{
    public function __construct(private readonly array $config) {}

    /** @param  iterable<string>  $files  paths or base names */
    public function filter(iterable $files): Collection
    {
        return collect($files)->filter(fn ($file) => $this->accepts((string) $file))->values();
    }

    public function accepts(string $file): bool
    {
        $name = Str::afterLast($file, '/');

        if (in_array($name, $this->list('not'), true)) {
            return false;
        }

        if ($this->list('contains') && ! Str::contains($name, $this->list('contains'))) {
            return false;
        }

        if ($this->list('notContains') && Str::contains($name, $this->list('notContains'))) {
            return false;
        }

        return ! isset($this->config['type']) || Str::endsWith($name, $this->config['type']);
    }

    private function list(string $key): array
    {
        return array_values(array_filter((array) ($this->config[$key] ?? []), 'strlen'));
    }
}
