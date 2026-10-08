<?php

namespace Go2Flow\Ezport\ContentTypes\Helpers;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Serializes "find, else create" of a Generic per project, type and unique_id.
 *
 * generic_models has no unique key on (project_id, type, unique_id), so two queue workers
 * importing the same new element at the same time both found nothing and both inserted a row.
 */
class CreationLock
{
    private const SECONDS = 30;

    public static function run(int $projectId, string $type, ?string $uniqueId, Closure $callback): mixed
    {
        if ($uniqueId === null || $uniqueId === '') {
            return $callback();
        }

        return Cache::lock(self::key($projectId, $type, $uniqueId), self::SECONDS)
            ->block(self::SECONDS, $callback);
    }

    public static function key(int $projectId, string $type, string $uniqueId): string
    {
        return 'ezport-generic-create:'.$projectId.':'.mb_strtolower($type).':'.md5($uniqueId);
    }
}
