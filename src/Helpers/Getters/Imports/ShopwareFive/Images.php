<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\ShopwareFive;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class Images extends BaseInstructions implements InstructionInterface {

    public function get() : array
    {
        return [
            Set::ShopImport('Images')
                ->type('Image')
                ->api(Get::api('shopFive'))
                ->uniqueId('id')
                ->job(
                    Set::Job()
                        ->config([
                            'type' => 'images'
                        ])
                )->items(
                    fn (Api $api): Collection => collect($api->media()->limit(7000)->get()->body()->data)->pluck('id')
                )->process(
                    fn (Collection $chunk, Api $api): Collection => $chunk->map(
                        fn ($id) => $api->media()->find($id)->body()->data
                    )
                )->properties(
                    fn ($image) => [
                        'id' => $image->id,
                        'name' => $image->name,
                        'description' => $image->description,
                        'path' => $image->path,
                        'extension' => $image->extension,
                        'album_id' => $image->albumId,
                    ]
                ),
        ];
    }
}
