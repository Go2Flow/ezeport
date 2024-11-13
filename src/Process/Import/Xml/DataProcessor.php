<?php

namespace Go2Flow\Ezport\Process\Import\Xml;

use Exception;
use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Types\XmlImport;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Orchestra\Parser\Xml\Facade as XmlParser;

class DataProcessor
{
    private PrepText $textPrepper;

    public function __construct(private Project $project)
    {
        $this->textPrepper = new PrepText;
    }

    public function dataToObjects(Collection $collection, string $key)
    {
        return $this->createObject(
            $collection,
            Find::instruction($this->project, 'Import')->find($key)
        );
    }

    private function createObject(Collection $collection, XmlImport $structure)
    {
        return $collection->map(
            function ($text) use ($structure) {

                if (!($content = $this->prepareContent($text, $structure))) return null;

                return $this->toClass(
                    $content,
                    $structure->get('type') ?? $structure->getKey(),
                );
            }
        )->filter();
    }

    private function toClass(Collection|array $content, string $class): Generic
    {
        $class = new Generic(
            [
                'type' => $class,
                'unique_id' => $content['unique_id'],
                'project_id' => $this->project->id
            ]
        );

        $class->setContentAndRelations($content);

        return $class->processRelations()
            ->updateOrCreate(true)
            ->setRelations();
    }

    private function prepareContent($text, xmlImport $structure): ?Collection
    {
        $data = $this->parseXml(
            $text, $structure->get('values') ?? [],
            $structure->get('attributes') ?? []
        );

        if ($structure->get('updateIf')) {

            foreach ($structure->get('updateIf') as $key => $value) {

                if ($data[$key] !=  $value) return null;
            }
        }

        foreach ($structure->get('components') ?? [] as $component) {

            $data[$this->prepareKey($component->getKey())] = $this->preparecomponent(
                $component,
                $text
            )->flatMap(
                fn ($content) => $this->createObject(
                    collect($content),
                    $component
                )
            );
        }

        foreach ($structure->get('arrays') ?? [] as $component) {

            $data[$this->prepareKey($component->getKey())] = $this->preparecomponent(
                $component,
                $text
            )->map(fn ($content) => $this->prepareContent($content, $component));
        }

        if ($closure = $structure->get('closure')) {

            $data = $data->merge($closure($this->extractText($text)));
        }

        return $data;
    }

    private function prepareComponent($component, $text)
    {

        return $this->textPrepper
            ->setText(
                Str::of($text),
                $path  = collect($component->get('path'))
            )->process($path->pop());
    }

    private function prepareKey($key)
    {
        return Str::of($key)->snake()->plural()->toString();
    }

    private function parseXml($text, $values, $attributes): Collection
    {
        $content = $this->extractText($text);

        return ($values
            ? collect($values)->map(
                fn ($value) => $this->getXmlValue($content, $text, $value)
            )
            : collect()
        )->merge(
            $attributes
                ? collect($attributes)->map(
                    fn ($attribute) => $this->getXmlAttribute($content, $text, $attribute)
                )
                : collect()
        );
    }

    private function getXmlValue($content, $text, $value)
    {

        if (is_array($value)) {

            $string = '';
            foreach ($value as $val) {

                $string .= '-' . $this->getXmlValue($content, $text, $val);
            }

            return Str::after($string, '-');
        }

        if (Str::of($value)->contains('.')) {
            $path = Str::of($value)->explode('.');
            $content = $this->textPrepper->setText($text, $path)->getText();
            $content = $this->extractText($content);
        }
        if (!$content) return null;

        return trim((string) $content->$value ?: (string) $content);
    }

    private function getXmlAttribute($content, $text, $attribute)
    {

        if (is_array($attribute)) {

            $string = '';
            foreach ($attribute as $val) {

                $string .= '-' . $this->getXmlAttribute($content, $text, $val);
            }

            return Str::after($string, '-');
        }

        $attribute = Str::of($attribute);

        if ($attribute->contains('.')) {
            $path = $attribute->explode('.');
            $content = $this->textPrepper->setText($text, $path)->getText();

            $content = $this->extractText($content);

            $attribute = Str::of($path->pop());
        }

        if ($attribute->contains('::')) {

            [$tag, $attribute] = $attribute->explode('::');

            $content = $content->children()->$tag;
        }

        if (!$content) return null;

        return trim((string) $content->attributes()[(string) $attribute]);
    }

    private function extractText($text)
    {
        if (!trim($text instanceof Stringable ? $text->toString() : $text)) return null;

        try {
            return XMLParser::extract($text)->getContent();
        } catch (Exception $e) {

            Log::info([$e->getMessage(), $text]);

        }
    }
}
