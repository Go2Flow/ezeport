<?php

namespace Go2Flow\Ezport\Process\Import\Shopware;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Types\ShopImport as SetShopImport;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportImportException;
use Go2Flow\Ezport\Process\Import\Helpers\HasStructure;
use Go2Flow\Ezport\Process\Jobs\ShopImport;
use Illuminate\Support\Collection;

class Controller
{
    use HasStructure;

    private $structure;
    private $api;

    public function __construct(private Project $project, private array $config)
    {
        $this->structure = Find::instruction($project, 'import')->find($config['key']);

        $this->api = $this->structure->get('api')($project);

        if (!$this->structure instanceof SetShopImport) throw new EzportImportException ("The found file is not of the correct type", 1);
    }

    public function assign() : Collection
    {
        $items = $this->structure->get('items')($this->api);

        return $items == null
            ? collect([new ShopImport($this->project->id, $this->config, collect())])
            : $items->chunk(25)
            ->map(
                fn ($chunk) => new ShopImport($this->project->id, $this->config, $chunk)
            );
    }

    public function process($chunk) : void
    {
        collect($this->structure->get('process')($chunk, $this->api))
            ->each(function ($item) {

                $response = $this->prepareContent($item, $this->structure->get('uniqueId'));

                $this->createGenericModel($response);
            });
    }

    private function createGenericModel($array) : void
    {
        $class = new Generic(array_merge([
            'project_id' => $this->project->id,
            'type' => $this->structure->get('type'),
        ], $array['uniqueId'] ? ['unique_id' => $array['uniqueId']] : []));

        $class->properties($array['content']['properties']);
        $class->shop($array['content']['shop']);

        $class->updateOrCreate(true);
    }

    private function prepareContent($item, string $identifier) : array
    {
        $array = [];
        $uniqueId = $item->{$identifier} ?? null;

        foreach (['properties', 'shop'] as $attribute) {

            $array[$attribute] = [];

            foreach ($this->structure->get($attribute) as $closure) {

                $array[$attribute] = array_merge($array[$attribute], $closure($item));
            }

            if (!$uniqueId && isset($array[$attribute][$identifier]))
            {
                $uniqueId = $array[$attribute][$identifier];
            }
        }

        return ['content' => $array, 'uniqueId' => $uniqueId];
    }
}
