<?php

namespace Go2Flow\Ezport\Instructions\Setters\Types;

use Closure;
use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\ContentTypes\Helpers\TypeGetter;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Getters\GetProxy;
use Go2Flow\Ezport\Instructions\Setters\Interfaces\JobInterface;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Go2Flow\Ezport\Models\GenericModel;
use Go2Flow\Ezport\Process\Jobs\UploadWithInstruction;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Collection;

class Upload extends Basic implements JobInterface
{

    protected Collection $getters;
    protected UploadProcessor|GetProxy|null|string $processor = null;
    protected ?closure $items = null;
    protected array $config = [];
    protected array $components = [];
    protected int $chunk = 25;

    public function __construct(string $key)
    {
        $this->getters = collect();
        $this->key = $this->processKey($key);
        $this->job = Set::job()
            ->class(UploadWithInstruction::class);
    }

    /**
     * The items closure must return either a collection or a builder.
     * In the case of a builder, the program will add that updated and touched must be true.
     * In the case of a collection, this collection should only contain ids (e.g. pluck).
     */

    public function items(closure $items): self
    {

        $this->items = $items;

        return $this;
    }

    /**
     * When the items are uploaded the program will run through all of the fields.
     * These will then be added to the upload array by the name.
     * If the closure evaluates to null it will not be added to the array.
     */

    public function field(UploadField|array $field): self
    {

        if (!$field instanceof UploadField) {
            foreach ($field as $key => $value) {

                $field = Set::UploadField($key)->field($value);
                break;
            }
        }

        $this->checkAndRemoveDuplicateKeyFromGetters($field->getKey());
        $this->getters->push($field);

        return $this;
    }

    /**
     * Add multiple fields at once. Will be passed to the field method.
     */

     public function fields(array|Collection $fields): self
     {

         foreach ($fields as $field) $this->field($field);

         return $this;
     }

    /**
     * Add another SetUpload as a child to the current SetUpload.
     * It will be passed to the UploadFields as the third parameter
     * It will be passed to the processor as the fourth parameter
     * In both cases, you can access it there by its key
     */

    public function components(array $components): self
    {
        collect($components)->each(
            fn ($component) => $this->component($component)
        );

        return $this;
    }

    public function component(Upload|Set $component) {

        $this->components[$component->getKey()] = $component;

        return $this;
    }

    /** add items to config so that it is available to the fields */

    public function config(array $config): self
    {

        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Size of the chunk to be passed to a job (default 25). Shrink this if jobs are timing out.
     */

    public function chunk(int $chunk) {

        $this->chunk = $chunk;

        return $this;
    }

    /** drops all fields. Useful when calling a pre-fabricated upload via 'Find' and the fields there are not correct*/

    public function dropFields(): self
    {

        $this->getters = collect();

        return $this;
    }

    /** drop specific field. Useful when calling a pre-fabricated upload via 'Find' and the fields there are not correct  */

    public function dropField(string $key) : self
    {
        $this->getters = $this->getters->filter(fn ($item) => ! $item->hasKey($key));

        return $this;
    }


    /**
     * The items and the api will be passed into the processor at run time.
     */

    public function processor(string|UploadProcessor|GetProxy $processor): self
    {

        $this->processor = $processor;

        return $this;
    }

    /**
     * An instance of the Class Generic will be transformed into a shop array.
     */

    public function toShopArray(Generic $model): array
    {
        $tempConfig = $this->config;

        $fields = $this->getters
            ->flatMap(
                fn ($getter) => $this->prepareField(
                    $getter->setProject($this->project)
                        ->process($model, $this->config, $this->components)
                )
            )->filter(fn ($field) => $field !== null)
            ->toArray();

        $this->config = $tempConfig;

        return $fields;
    }

    /**
     * returns the ids of the items that should be uploaded.
     * these can then be passed to the processor.
     */

    public function pluck(): Collection
    {
        $response = $this->builder();

        if (!$response instanceof Builder) return $response->chunk(25);

        return $response->whereUpdated(true)
            ->whereTouched(true)
            ->pluck('id')
            ->chunk($this->chunk);
    }

    /**
     * Expects a Collection of Ids which it will prepare for the processor
     */

    public function prepareItems(Collection $ids): Collection
    {

        return GenericModel::Wherein('id', $ids)
            ->get()
            ->toContentType()
            ->map
            ->setStructure($this);
    }

    /**
     * returns the processor that has been assigned
     * if the processor field is a string it will look for that processor in the project
     * if the processor field is null it will look for a processor with the same name as the key
     */

    public function getProcessor()
    {

        if ($this->processor) {

            if ($this->processor instanceof GetProxy) {

                $processor = ($this->processor)($this->project);
            }

            if ($this->processor instanceof UploadProcessor) {
                $processor = $this->processor
                    ->setProject($this->project);
            }
        } else {
            $processor = Get::processor($this->key)($this->project);
        }

        return $processor->setComponents($this->components);
    }

    private function builder(): TypeGetter|Collection|Builder
    {
        return $this->items
            ? ($this->items)()
            : Content::type($this->key, $this->project);
    }

    private function prepareField(?array $response): array
    {

        if ($response === null) return [];

        $prepared = $this->seperateArrayAndConfigAndPrepare($response['value']);

        return $response['key']
            ? [$response['key'] => $prepared]
            : $prepared;
    }

    private function seperateArrayAndConfigAndPrepare($response)
    {
        if (is_array($response) && isset($response['array']) && isset($response['config'])) {

            $this->config = array_merge_recursive($this->config, $response['config']);

            return $response['array'];
        }

        return $response instanceof Collection
            ? $response->toArray()
            : $response;
    }

    private function checkAndRemoveDuplicateKeyFromGetters(null|string $key): void
    {
        if ($key === null) return;

        if (($items = $this->getters->filter(fn ($item) => $item->hasKey($key)))->count() > 0) {

            $this->getters->forget($items->keys()->first());
        }
    }
}
