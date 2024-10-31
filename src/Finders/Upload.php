<?php

namespace Go2Flow\Ezport\Finders;

use Go2Flow\Ezport\Helpers\Getters\Uploads\ShopwareSix\Articles;
use Go2Flow\Ezport\Instructions\Setters\Types\Upload as SetUpload;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Errors\EzportFinderException;

class Upload extends Base{

    protected function getObject(Project $project, string $string) : SetUpload
    {

        foreach (Find::config($project)['uploads'] ?? [] as $upload) {

            if ($instruction = (new $upload($project))->find($string)) return $instruction;
        }

        throw new EzportFinderException('Upload ' . $string . " not found. Have you specified the correct files in the Customer config under 'uploads'?");

    }
}
