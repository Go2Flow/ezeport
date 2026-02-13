<?php

namespace Go2Flow\Ezport\Process\Import\Shopware;

use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Types\ShopImport as SetShopImport;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportImportException;
use Go2Flow\Ezport\Process\Import\Helpers\HasStructure;
use Go2Flow\Ezport\Process\Jobs\ProcessInstruction;
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

        if (!$this->structure instanceof SetShopImport) throw new EzportImportException("The found file is not of the correct type", 1);
    }

    public function assign() : Collection
    {
        $items = $this->structure->get('items')($this->api);

        return $items->chunk(25)
            ->map(
                fn ($chunk) => new ProcessInstruction(
                    $this->project->id,
                    ['items' => $chunk, 'instructionType' => 'import', 'key' => $this->config['key']]
                )
            );
    }

    public function process($chunk) : void
    {
        $this->structure->get('process')($chunk);
    }
}
