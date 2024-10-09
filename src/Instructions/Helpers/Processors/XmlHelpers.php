<?php

namespace Go2Flow\Ezport\Instructions\Helpers\Processors;

use SimpleXMLElement;

trait XmlHelpers {

    public function prepareForXml(string $string, array $data) : SimpleXMLElement
    {
        $xml = new SimpleXMLElement($string);
        $this->arrayToXml(
            $data,
            $xml
        );

        return $xml;
    }

    private function arrayToXml(array $data, SimpleXMLElement &$xml ) {


        foreach( $data as $key => $value ) {

            if( is_array($value) && !isset($value['attribute']) ) {

                if(!is_numeric($key)){
                    $subnode = $xml->addChild($key);
                    $this->arrayToXml($value, $subnode);
                }
                else{
                    $this->arrayToXml($value, $xml);
                }

            } elseif (isset($value['attribute'])) {

                $xml->addAttribute($key, $value['attribute']);
            }
             else {
                $xml->addChild($key, $value);
            }
        }

    }
}
