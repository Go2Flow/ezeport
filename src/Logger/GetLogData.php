<?php

namespace Go2Flow\Ezport\Logger;

use Go2Flow\Ezport\Models\Action;
use Go2Flow\Ezport\Models\Activity;
use Go2Flow\Ezport\Models\Error;
use Go2Flow\Ezport\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GetLogData
{
    private Builder $builder;

    public function __construct()
    {
        $this->builder = Activity::Query();
    }

    public function id(int $id): self
    {
        return $this->set(
            $this->builder->whereGenericModelId($id)
        );
    }

    public function uniqueId(string $unique_id): self
    {
        return $this->set(
            $this->builder->whereUniqueId($unique_id)
        );
    }

    public function action(Action|int $action): self
    {

        return $this->set(
            $this->builder->whereActionId(is_int($action) ? $action : $action->id)

        );
    }

    public function actions(array|Collection $actions) : self {
        return $this->set(
            $this->builder->whereIn('action_id', $actions)
        );
    }

    public function project(int|Project $project) : self
    {
        $actionIds = (is_int($project) ? Project::find($project) : $project)->actions()->pluck('id');

        return $this->whereIn(
            'action_id',
            $actionIds,
        );
    }

    public function modelType(string $type) : self
    {
        return $this->set(
            $this->builder->whereGenericModelType($type)
        );
    }

    public function query() : Builder
    {
        return $this->builder;
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

    public function activity(): self
    {
        return $this->set(Activity::query());
    }

    public function error(): self
    {
        return $this->set(Error::query());
    }

    private function whereType(string $type) : self
    {
        return $this->where('activity_type', $type);
    }

    private function where(string $column, $value, string $operator = '=') : self
    {
        $this->builder->where($column, $operator, $value);
        return $this;
    }

    private function whereIn(string $column, array $values) : self
    {
        $this->builder->whereIn($column, $values);

        return $this;
    }

    private function set(Builder $builder) : self {

        $this->builder = $builder;
        return $this;
    }

    private function groupByAction(): Collection
    {
        $modelByAction = $this->builder->get()->groupBy('action_id');

        $actions = Action::query()
            ->with('project')
            ->whereIn('id', $modelByAction->keys())
            ->get()
            ->keyBy('id');

        return $modelByAction->map(function ($models, $actionId) use ($actions) {
            $action = $actions->get($actionId);

            return [
                'action' => $action,
                'project' => $action?->project,
                'models' => $models,
            ];
        })->values();
    }
}
