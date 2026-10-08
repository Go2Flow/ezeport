<?php

namespace Go2Flow\Ezport\Instructions\Getters;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Models\Project;

/**
 * Deferred lookups: each returns a GetProxy that resolves the instruction for the project
 * it is invoked with and replays the methods chained on it.
 */
class Get
{
    public static function upload(string $type): GetProxy
    {
        return new GetProxy(fn (Project $project) => Find::upload($project, $type));
    }

    public static function import(string $type): GetProxy
    {
        return new GetProxy(fn (Project $project) => Find::import($project, $type));
    }

    public static function api(string $type): GetProxy
    {
        return new GetProxy(fn (Project $project) => Find::api($project, $type));
    }

    public static function processor(string $type): GetProxy
    {
        return new GetProxy(fn (Project $project) => Find::processor($project, $type));
    }

    public static function transform(string $type): GetProxy
    {
        return new GetProxy(fn (Project $project) => Find::transform($project, $type));
    }
}
