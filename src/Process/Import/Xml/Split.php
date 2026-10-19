<?php

namespace Go2Flow\Ezport\Process\Import\Xml;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportImportException;
use Go2Flow\Ezport\Process\Jobs\XmlImport;
use Illuminate\Support\Collection;
use XMLReader;

class Split
{
    private Collection $collection;

    public function __construct(private string $path, private Project $project) {}

    public function batch(string $name): self
    {
        $this->assertReadable();

        $instruction = Find::instruction($this->project, 'Import')->byKey($name);

        $this->collection = collect([$name => $this->elements([...$instruction->get('path')])]);

        return $this;
    }

    /**
     * The outer XML of every element at the end of $path, e.g. each Artikel of
     * ['Artikeldaten', 'Artikel'], each wrapped in a collection.
     */
    public function elements(array $path): Collection
    {
        $this->assertReadable();

        $xml = new XMLReader;

        if (! $xml->open($this->path)) {
            $this->cannotOpen();
        }

        libxml_use_internal_errors(true);

        $node = array_pop($path);

        // only the parents: stopping on the first $node here would make the search below skip it
        foreach ($path as $parent) {
            while ($xml->read()) {
                if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == $parent) {
                    break;
                }
            }
        }

        if ($errors = libxml_get_errors()) {

            $string = '';

            foreach ($errors as $error) {

                $string .= $error->message.'at '.$error->line.PHP_EOL;
            }

            throw new EzportImportException(json_encode(['project_id' => $this->project->id, 'message' => $string]));
        }

        $elements = collect();

        while ($xml->read() && ! ($xml->nodeType === XMLReader::ELEMENT && $xml->name === $node)) {

        }

        while ($xml->nodeType === XMLReader::ELEMENT && $xml->name === $node) {
            $elements->push(collect($xml->readOuterXML()));
            $xml->next($node);
        }

        return $elements;
    }

    private function assertReadable(): void
    {
        if (! is_readable($this->path)) {
            $this->cannotOpen();
        }
    }

    private function cannotOpen(): never
    {
        throw new EzportImportException(json_encode(['project_id' => $this->project->id, 'message' => "Could not open file {$this->path}"], JSON_UNESCAPED_SLASHES));
    }

    public function getCollection(): Collection
    {
        return $this->collection;
    }

    public function getJobs(): Collection
    {
        return $this->collection->flatMap(
            function ($item, $key) {
                return $item->map(
                    fn ($xml) => new XmlImport($this->project->id, $xml, $key)
                );
            }
        );
    }
}
