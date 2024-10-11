<?php

namespace Go2Flow\Ezport\Logger;

use Go2Flow\Ezport\Models\Action;
use Go2Flow\Ezport\Models\Activity;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetLogData
{
    private Builder $activity;

    public function __construct()
    {
        $this->activity = Activity::Query();
    }

    public function id(int $id): self
    {
        return $this->set(
            $this->activity->whereGenericModelId($id)
        );
    }

    public function uniqueId(string $unique_id): self
    {
        return $this->set(
            $this->activity->whereUniqueId($unique_id)
        );
    }

    public function action(Action|int $action): self
    {
        return $this->set(
            $this->activity->whereActionId(is_int($action) ? $action : $action->id)

        );
    }

    public function project(int|Project $project) : self
    {
        $actionIds = (is_int($project) ? Project::find($project) : $project)->actions()->pluck('id');

        return $this->where(
            'action_id',
            $actionIds,
            'in'
        );
    }

    public function modelType(string $type) : self
    {
        return $this->set(
            $this->activity->whereGenericModelType($type)
        );
    }

    public function query() : Builder
    {
        return $this->activity;
    }

    public function get() : Collection {

        return $this->groupByAction();
    }

    public function changes() : self {

        return $this->whereType(
            'generic_model'
        );
    }

    public function jobs() : self {

        return $this->whereType('failed_job');
    }

    public function shopware() : self {

        return $this->whereType('shop');
    }

    public function api() : self {

        return $this->whereType('api');
    }

    public function set(Builder $activity) : self {

        $this->activity = $activity;
        return $this;
    }

    private function whereType(string $type) : self
    {
        return $this->where('activity_type', $type);
    }

    private function where(string $column, $value, string $operator = '=') : self
    {
        $this->activity->where($column, $operator, $value);
        return $this;
    }

    private function groupByAction(): Collection
    {
        return $this->activity->get()->maptoGroups(
            fn ($item) => [$item->action_id => $item]
        )->map(
            fn ($item, $key) => [
                'action' => $action = Action::find($key),
                'project' => $action->project,
                'activity' => $item
            ]
        )->values();
    }
}
