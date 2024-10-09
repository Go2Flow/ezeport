<?php

namespace Go2Flow\Ezport\Connectors\ShopwareSix;

use Go2Flow\Ezport\Finders\Api;
use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;

class ShopSix {

    /**
     * standard filter for shopware six api
     * standard values: field = id, value = '', type = equals
     * using a key that is not field, value or type will throw an exception
     */

    public static function filter (array $config) : array {

        foreach ($config as $key => $value) {

            if (!in_array($key, ['field', 'value', 'type'])) {
                throw new \InvalidArgumentException("You can only use 'field', 'value', and 'type' as keys. the key $key is invalid");
            }
        }

        return array_merge(['field' => 'id', 'value' => '','type' => 'equals'], $config);
    }

    public static function filters (array $config) : array {

        if (!isset($config['queries'])) {
            throw new \InvalidArgumentException("You must provide a queries key in the config array");
        }

        foreach ($config as $key => $value) {

            if (!in_array($key, ['type', 'operator', 'queries'])) {
                throw new \InvalidArgumentException("You can only use 'field', 'value', and 'type' as keys. the key $key is invalid");
            }
        }

        return array_merge([
            "type" => "multi",
            "operator" => "and",
        ],
        $config);
    }

    public static function association(array $config) : array{

        $response = [];
        foreach ($config as $item) {
            $response[$item] = [];
        }

        return $response;
    }

    public static function api (Project $project) : Api {

        return Find::api($project, 'shopSix');
    }
}
