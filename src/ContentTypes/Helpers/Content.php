<?php

namespace Go2Flow\Ezport\ContentTypes\Helpers;

use Go2Flow\Ezport\Models\Project;

class Content {

    /**
     * @param string $type
     * @param Project $project
     * @return TypeGetter
     */

    public static function type(string $type, Project $project) : TypeGetter{

        return new TypeGetter($type, $project);
    }
}
