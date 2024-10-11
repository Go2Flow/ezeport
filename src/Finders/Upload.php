<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Getters\Uploads\StandardShopSix;
use Go2Flow\Ezport\Instructions\Setters\Types\Upload as SetUpload;
use Go2Flow\Ezport\Models\Project;

class Upload extends Base{

    protected function getObject(Project $project, string $string) : SetUpload
    {

        foreach ($this->mergeConfigWithStandard($project, 'uploads', StandardShopSix::class) as $upload) {

            if ($instruction = (new $upload($project))->find($string)) return $instruction;
        }

        throw new \Exception("Upload {$string} not found. Check your config file whether the correct upload is set.");
    }
}
