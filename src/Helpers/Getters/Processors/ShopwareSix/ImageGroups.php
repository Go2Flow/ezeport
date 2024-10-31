<?php

namespace Go2Flow\Ezport\Helpers\Getters\Processors\ShopwareSix;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Helpers\Traits\Processors\GeneralHelpers;
use Go2Flow\Ezport\Helpers\Traits\Processors\StandardShopSixArticle;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class ImageGroups extends BaseInstructions implements InstructionInterface
{
    use GeneralHelpers, StandardShopSixArticle;

    public function get() : array
    {
        return [
            Set::UploadProcessor('imageGroup')
                ->process(
                    function (Collection $items, Api $api) {

                        $ftp = Find::Api($this->project, 'ftp');
                        $content = $items->flatMap(
                            function ($item) use ($ftp) {

                                if (count($images = $item->properties('images')->pluck('name')) == 0 || !$item->shopware('images')) return collect();

                                $files = $ftp->image()
                                    ->find($images);

                                return $files->map(
                                    fn ($file, $key) => [
                                        'file' => $file,
                                        'id' => $item->shopware('images')[$key]
                                    ]
                                );
                            }
                        );

                        if ($content->count() > 0) {

                            foreach ($content as $item) {
                                $api->media()
                                    ->upload(
                                        ['file' => $item['file']],
                                        $item['id']
                                    );
                            }
                        }
                    }
                ),
        ];
    }
}
