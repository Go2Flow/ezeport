<?php

namespace Go2Flow\Ezport\ContentTypes\Helpers;

use Go2Flow\Ezport\ContentTypes\ActivityLog;
use Go2Flow\Ezport\ContentTypes\Generic;
use Go2Flow\Ezport\Models\Action;

use Illuminate\Support\Collection;

class Log
{
    private ?Action $action;

    public function __construct(private Generic $current)
    {
        $this->action  = $this->current->project()->currentAction();
    }

    public function delete(): void
    {
        if (! $this->action) return;

        $this->getActivityLogObject($this->current->type)
            ->log('deleted');
    }

    public function change(array $original, $exists = true): void
    {
        if (! $this->action || count($difference = $this->getDifference($original)) === 0) return;

        $this->getActivityLogObject('standard')
            ->contentType($this->current->getType())
            ->model($this->current)
            ->properties($difference)
            ->log($exists ? 'updated' : 'created');
    }

    public function hasError(array $errors): void
    {
        $this->getActivityLogObject('standard')
            ->contentType($this->current->getType())
            ->model($this->current)
            ->properties($errors)
            ->failedJob()
            ->log('error');
    }

    private function getActivityLogObject(string $type): ActivityLog
    {
        return (new ActivityLog)
            ->type($type)
            ->uniqueId($this->current->unique_id)
            ->action($this->action);
    }

    private function getDifference(array $original): array
    {
        $diff = [];

        foreach (['content' => 'properties', 'shop' => 'shop'] as $key => $type) {

            $response = $this->compareRecursively(
                collect( $original[$key] ?? []),
                collect($this->current->$type())
            );

            if ($response->count() > 0) {
                $diff[$type] = $response;
            }
        }

        return $diff;
    }

    private function compareRecursively(Collection $original, Collection $current) : Collection
    {
        return $current->map(
            function ($item, $key) use ($original) {

                if (is_array($item) || $item instanceof Collection) {

                    return ($result = $this->compareRecursively(
                        collect($original[$key] ?? []),
                        collect($item)
                    ))->count() > 0
                        ? $result
                        : null;
                }

                if (!$original->has($key) || $item != $original[$key]) {

                    return $item;
                }
            }
        )->filter();
    }
}
