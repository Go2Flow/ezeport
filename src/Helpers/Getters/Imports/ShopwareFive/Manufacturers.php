<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\ShopwareFive;

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
                ->type('Manufacturer')
                ->uniqueId('id')
                ->api(Get::api('shopFive'))
                ->job(
                    Set::Job()
                        ->config([
                            'type' => 'manufacturers'
                        ])
                )->items(
                    fn (Api $api): Collection => collect($api->manufacturer()->get()->body()->data)->pluck('id')
                )->process(
                    fn (Collection $chunk, Api $api): Collection => $chunk->map(
                        fn ($id) => $api->manufacturer()->find($id)->body()->data
                    )
                )->properties(
                    fn ($manufacturer) => [
                        'id' => $manufacturer->id,
                        'text' => $manufacturer->name,
                    ]
                ),
        ];
    }
}
