<?php

namespace Go2Flow\Ezport\Import\Xml;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;

class PrepText
{

    private $text;

    public function setText(string|Stringable $text, ?Collection $path = null): PrepText
    {
        $this->text = $this->getPartial(
            is_string($text) ? Str::of($text) : $text,
            $path
        );

        return $this;
    }

    public function cleanText()
    {
        $this->text = str_replace('&amp;', '', $this->text);

        return $this;
    }

    public function process(string $identifier): Collection
    {
        return $this->prepare($this->text, $identifier);
    }

    public function getText()
    {
        return $this->text;
    }

    public function pullText()
    {
        $text = $this->text;

        $this->text = Str::of('');

        return $text;
    }

    private function prepare(?Stringable $string, string $identifier): Collection
    {
        return $this->explode($string, $identifier)
            ->map(
                fn ($item) => Str::of($item)->trim()
                    ->when(
                        substr_count($item, '<') > substr_count($item, '>'),
                        fn ($item) => $item->append('>')
                    )->append('</' . $identifier . '>')
                    ->when(
                        Str::of($item)->afterLast('/n')->contains(' /></'),
                        fn ($item) => $item->afterLast('/n')->contains($identifier) ? $item->replace(' /></', '></') : $item
                    )
            )->filter(
                fn ($item) => Str::contains($item, '<' . $identifier . ' ') || Str::contains($item, '<' . $identifier . '>')
            );
    }

    private function explode(Stringable $string, $identifier): Collection
    {
        $string = $this->trimText($string, $identifier);

        if ($string->contains('</' . $identifier . '>') && $string->afterLast('</' . $identifier . '>')->contains('<' . $identifier)) {

            return $this->explode(
                $string->before($newString = $string->afterLast('</' . $identifier . '>')),
                $identifier
            )->push(
                ...$this->explode(
                    $newString,
                    $identifier
                )
            );
        }

        return ($string->contains('</' . $identifier . '>')
            ? $string->explode('</' . $identifier . '>')
            : $string->explode('/>'))
            ->map(
                fn ($item) => substr_count($item, '<' . $identifier . ' ') > 1 || substr_count($item, '<' . $identifier . '>') > 1
                    ? $this->explode(Str::of($item), $identifier)
                    : $item
            )->flatten();
    }

    private function trimText(Stringable $string, string $identifier): Stringable
    {
        foreach ([' ', '>'] as $end) {

            if ($string->contains('<' . $identifier . $end) && ($before = $string->before('<' . $identifier . $end))->length() > 0) {
                $string = $string->after($before);
                break;
            }
        }

        return $string;
    }

    private function getPartial(Stringable $text, Collection|null $path): Stringable
    {
        if (!$path || $path->count() == 1) return $text;

        $identifier = $path->shift();

        if (!$text->contains('</' . $identifier)) return Str::of('');

        return $this->getPartial(
            $text->before('</' . $identifier . '>')->after('<' . $identifier)->after('>'),
            $path
        );
    }
}
