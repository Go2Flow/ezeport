<?php

namespace Go2Flow\Ezport\Helpers\Getters\Imports\ShopwareFive;

use Go2Flow\Ezport\ContentTypes\Helpers\Content;
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
                ->api(Get::api('shopFive'))
                ->items(
                    function ($api) {

                        $firstResponse = $api
                            ->media()
                            ->limit(50)
                            ->get()
                            ->body();

                        $ids = collect($firstResponse->data)->pluck("id");
                        for ($i = 1; $i < $firstResponse->total / 50; $i++) {
                            $response = $api
                                ->media()
                                ->limit(50)
                                ->start($i * 50)
                                ->get()
                                ->body();

                            if ($response) {
                                $ids = $ids->merge(collect($response->data)->pluck("id"));
                            }
                        }

                        return $ids;

                    }
                )->process(
                    function ($chunk, $api) {

                        foreach ($chunk as $id) {
                            $image = $api->media()->find($id)->body()->data;

                            Content::Type('Image', $this->project)
                                ->updateOrCreate([
                                    'unique_id' => $image->id,

                                ], [
                                    'name' => $image->name ?? null,
                                    'properties' => [
                                        'description' => $image->description,
                                        'path' => $image->path,
                                        'extension' => $image->extension,
                                        'album_id' => $image->albumId,
                                    ]
                                ]);
                        }
                    }),
        ];
    }
}
