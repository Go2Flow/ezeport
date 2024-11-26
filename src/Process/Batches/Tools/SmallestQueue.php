<?php

namespace Go2Flow\Ezport\Process\Batches\Tools;

use Illuminate\Support\Facades\Queue;

class SmallestQueue {

    public static function get() : string
    {

        $queues = collect(config('horizon.defaults'))->map(
            fn ($supervisor) => $supervisor['queue'][0]
        );

        $smallestQueue = null;
        $smallestSize = PHP_INT_MAX;

        foreach ($queues as $queue) {
            $size = Queue::size($queue);
            if ($size < $smallestSize) {
                $smallestQueue = $queue;
                $smallestSize = $size;
            }
        }

        return $smallestQueue;
    }

}
