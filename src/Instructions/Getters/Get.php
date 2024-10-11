<?php

namespace Go2Flow\Ezport\Instructions\Getters;

use Go2Flow\Ezport\Finders\Find;
use Go2Flow\Ezport\Instructions\Setters\Types\Api;
use Go2Flow\Ezport\Instructions\Setters\Types\Transform;
use Go2Flow\Ezport\Instructions\Setters\Types\Upload;
use Go2Flow\Ezport\Instructions\Setters\Types\UploadProcessor;
use Go2Flow\Ezport\Models\Project;

class Get {

    /**
     * @return Upload
     */

    public static function upload(string $type)
    {
        return new GetProxy(fn (Project $project) => Find::upload($project, $type));
    }

    public static function import(string $type)
    {
        return new GetProxy(fn (Project $project) => Find::import($project, $type));
    }

    /**
     * @return Api
     */

    public static function api(string $type)
    {
        return new GetProxy(fn (Project $project) => Find::api($project, $type));
    }

    /**
     * @return UploadProcessor
     */

    public static function processor(string $type)
    {
        return new GetProxy(fn (Project $project) => Find::processor($project, $type));
    }

    /**
     * @return Transform
     */

    public static function transform(string $type)
    {
        return new GetProxy(fn (Project $project) => Find::transform($project, $type));
    }
}
