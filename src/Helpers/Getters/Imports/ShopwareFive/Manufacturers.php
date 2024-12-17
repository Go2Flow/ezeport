<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\ShopwareFive;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class Manufacturers extends BaseInstructions implements InstructionInterface {

    public function get() : array
    {
        return [
            Set::ShopImport('Manufacturers')
                ->api(Get::api('shopFive'))
                ->items(
                    fn ($api) => collect($api->manufacturers()->get()->body()->data)->pluck('id')
                )->process(
                    function ($chunk, $api) {

                        foreach ($chunk as $id) {
                            $manufacturer = $api->manufacturers()->find($id)->body()->data;

                            Content::type('Manufacturer', $this->project)
                                ->updateOrCreate([
                                    'unique_id' => $manufacturer->id,
                                ], [
                                    'name' => $manufacturer->name,
                                    'properties' => [
                                        'id' => $manufacturer->id,
                                        'text' => $manufacturer->name,
                                    ]
                                ]);
                        }
                    }),
        ];
    }
}
