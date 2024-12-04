<?php

namespace Go2Flow\Ezport\Helpers\Traits\Imports;

use Illuminate\Support\Collection;

trait ShopwareItemGetter {

    public function getItemsFromPages($api, \Closure $closure) : Collection
    {
        $total = 101;
        $page = 1;
        $items = collect();

        while (($page * 100) < $total) {

            $response = $closure($api, $page);

            if (!isset($response->total)) break;

            $total = $response->total;
            $page++;

            $items->push(... $response->data);
        }

        return $items;
    }
}
