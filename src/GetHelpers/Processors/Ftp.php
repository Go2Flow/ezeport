<?php

namespace Go2Flow\Ezport\GetHelpers\Processors;

use Go2Flow\Ezport\Finders\Abstracts\BaseInstructions;
use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Interfaces\InstructionInterface;
use Go2Flow\Ezport\Instructions\Getters\Get;
use Go2Flow\Ezport\Instructions\Traits\Processors\XmlHelpers;
use Go2Flow\Ezport\Instructions\Setters\Set;
use Illuminate\Support\Collection;

class Ftp extends BaseInstructions implements InstructionInterface
{
    use XmlHelpers;

    public function get() : array
    {
        return [
            Set::UploadProcessor('ftpOrder')
                ->api(Get::Api('ftp'))
                ->process(
                    function (Collection $items, Api $api, array $config) {

                        foreach ($items as $item) {

                            if ($item->shopware('state') === $this->project->cache('order_ids')['completed']) continue;

                            $data = $item->toShopArray();
                            $filename = $data['fileName'];
                            unset($data['fileName']);


                            $xml = $this->prepareForXml(
                                $config['xml'],
                                isset ($config['tagName']) ? [$config['tagName'] => $data] : $data
                            );


                            $dom = dom_import_simplexml($xml)->ownerDocument;
                            $dom->formatOutput = true;

                            $api->order()
                                ->upload(
                                    $filename,
                                    $dom->saveXML()
                                );

                            $item->shopware(['ftp' => 'uploaded']);
                            $item->updateOrCreate();
                        }
                    }
                ),

        ];
    }
}
