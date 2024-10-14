<?php

namespace Go2Flow\Ezport\Process\Import\Xml;

use Exception;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Mail\XmlImportError;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportXmlImportError;
use Go2Flow\Ezport\Process\Jobs\XmlImport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use XMLReader;

class Split{

    private Collection $collection;

    public function __construct(private string $path, private Project $project)
    {

    }

    public function batch(string $name) : self {

        $this->collection = collect();

        libxml_use_internal_errors(true);

        $instruction = Find::instruction($this->project, 'Import')->byKey($name);
        $path = collect([... $instruction->get('path')]);

        $xml = new XMLReader();

        if (! $xml->open($this->path)) {
            $this->sendEmail("Could not open file {$this->path}");
        }

        foreach ($path as $nodeName) {
            while ($xml->read()) {
                if ($xml->nodeType == XMLReader::ELEMENT && $xml->name == $nodeName) {
                    break;
                }
            }
        }

        if ($errors = libxml_get_errors()) {

            $string = '';

            foreach ($errors as $error) {

                $string .= $error->message . 'at ' . $error->line . PHP_EOL;
            }

            throw new EzportXmlImportError($string);
        }

        $node = $path->pop();
        $this->collection[$name] = collect();

        while($xml->name == $node)
        {
            $this->collection[$name]->push(collect($xml->readOuterXML()));

            $xml->next($node);
        }

        return $this;
    }

    public function getCollection() : Collection
    {
        return $this->collection;
    }

    public function getJobs() : Collection
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
