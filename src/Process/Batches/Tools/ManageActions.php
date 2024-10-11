<?php

namespace Go2Flow\Ezport\Process\Batches\Tools;

use Go2Flow\Ezport\Models\Action;
use Go2Flow\Ezport\Models\Project;
use Go2Flow\Ezport\Process\Jobs\FinishAction;
use Illuminate\Support\Collection;

class ManageActions {

    private ?Action $action;

    public function __construct(
        protected Project $project
    ){
        $this->action = null;
    }

    public function start(string $key, string $type, $queue): void
    {
        $this->project
            ->actions()
            ->whereActive(true)
            ->get()
            ->each->update([
                'active' => false,
                'finished_at'  => now()
            ]);

        $this->action = Action::create([
            'name' => $key,
            'type' => $type,
            'project_id' => $this->project->id,
            'queue' => $queue
        ]);

    }

    public function getQueue(): string
    {
        if ($this->action) return $this->action->queue;

        return $this->project
            ->actions()
            ->whereActive(true)
            ->first()?->queue ?? 'one';
    }

    public function finishAction(): ?Collection
    {
        if ($action = $this->project->actions()->whereActive(true)->orderBy('created_at')->first()) {

            return collect([new FinishAction($this->project->id, $action->id)]);
        }

        return collect();
    }

    public function setStep(string $step) : void
    {
        $this->action->update([
            'step' => $step
        ]);
    }
}
