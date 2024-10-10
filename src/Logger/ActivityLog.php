<?php

namespace Go2Flow\Ezport\Logger;

use Go2Flow\Ezport\Models\Action;
use Go2Flow\Ezport\Models\Project;

class ActivityLog {

    /**
     * @method static GetLogData id(int $id)
     * @method static GetLogData uniqueId(string $unique_id)
     * @method static GetLogData action(Action|int $action)
     * @method static GetLogData project(int|Project $project)
     * @method static GetLogData modelType(string $type)
     * @method static GetLogData changes()
     * @method static GetLogData jobs()
     * @method static GetLogData api()
     * @return GetLogData
     */

    public static function __callStatic(string $method, array $args) : GetLogData
    {
        return (new GetLogData)->$method(...$args);
    }
}
