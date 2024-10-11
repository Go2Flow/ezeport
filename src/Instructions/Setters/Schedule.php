<?php

namespace Go2Flow\Ezport\Instructions\Setters;

use Go2Flow\Ezport\Instructions\Setters\Tools\TimeChecker;
use Go2Flow\Ezport\Process\Batches\JobBatcher;
use Go2Flow\Ezport\Process\Jobs\AssignBatch;
use Go2Flow\Ezport\Upload\UploadManager;
use Illuminate\Bus\Batch;
use Illuminate\Cache\Lock;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

class Schedule extends Base
{

    protected ?string $event;
    protected ?string $type;
    protected array $times = [];
    protected ?array $days = null;
    protected bool $unique = false;
    protected ?Schedule $after = null;
    protected ?Stringable $key = null;

    protected array $daysArray = [
        'sunday' => 0,
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6
    ];

    protected array $numbers = [
        'one' => 1,
        'two' => 2,
        'three' => 3,
        'four' => 4,
        'five' => 5,
        'six' => 6,
        'seven' => 7,
        'eight' => 8,
        'nine' => 9,
        'ten' => 10,
        'fifteen' => 15,
        'twenty' => 20,
        'thirty' => 30,
        'fortyFive' => 45,
        'sixty' => 60,
    ];

    /**
     * set the event to 'import'. This will run the jobs called 'import' in the project's job instructions.
     * you will still need to set the type of import (full or partial)
     */

    public function import(): self
    {
        return $this->set('event', 'import');
    }

    /**
     * set the event to 'upload'. This will run the jobs called 'upload' in the project's job instructions.
     * you will still need to set the type of upload (full or partial)
     */

    public function upload(): self
    {
        return $this->set('event', 'upload');
    }

    /**
     * set the event to 'shopClean'. This will run the jobs called 'shopClean' in the project's job instructions.
     * you will still need to set the type of clean (full or partial)
     */

    public function shopClean(): self
    {
        return $this->set('event', 'shopClean');
    }

    /**
     * set the event to 'ftpClean'. This will run the jobs called 'ftpClean' in the project's job instructions.
     * you will still need to set the type of clean (full or partial)
     */

    public function ftpClean(): self
    {
        return $this->set('event', 'ftpClean');
    }

    /**
     * set the job type to 'full' This will run the job type 'full' in the project's job instructions.
     * you will still need to set the event (import, upload, shopClean, ftpClean)
     */

    public function full(): self
    {
        return $this->set('type', 'full');
    }

    /**
     * set the job type to 'full' This will run the job type 'partial' in the project's job instructions.
     * you will still need to set the event (import, upload, shopClean, ftpClean)
     */


    public function partial(): self
    {
        return $this->set('type', 'partial');
    }

    /**
     * set the task to be performed at a specific time of day
     */

    public function dailyAt(string $time): self
    {
        return $this->addToTimes('dailyAt', $time);
    }

    /**
     * this task should be unique and not run if it is already running
     */

    public function unique(): self
    {
        $this->unique = true;

        return $this;
    }

    /**
     * set the task to be performed only on week days
     */

    public function weekDays(): self
    {
        return $this->days([1, 2, 3, 4, 5]);
    }

    /**
     * set the task to be performed on the days specified. Days can be numerical (0-6) or written out (e.g. 'monday', 'tuesday')
     */

    public function days(array $days): self
    {
        $this->days = collect($days)->map(
            function ($day) {
                if (is_String($day)) return $this->daysArray[Str::lower($day)];
                if (is_int($day) && $day < 7 && $day >= 0) return $day;

                throw new \Exception("Days must be a week day written out or an integer between 0 and 6");
            }
        )->toArray();

        return $this;
    }

    /**
     * set the task to be performed between the times specified. Times must be in 24 hour format (e.g. '00:00', '23:59')
     */

    public function between(string $start, string $end): self
    {
        foreach (['start', 'end'] as $time) {
            if (!preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $$time)) {
                throw new \Exception("{$$time} is not a valid time");
            }
        }

        return $this->addToTimes('between', [
            'start' => $start,
            'end' => $end,
        ]);
    }

    /**
     * This is the key for the lock that will be used to prevent the task from running multiple times
     */

    public function setKey(int $key): self
    {
        $this->key = Str::of($this->project->identifier . '-' . $this->event . '-' . $this->type . '-' . $key);

        return $this;
    }

    /**
     * provide anoder Schedule task here to have it run directly after.
     * If you use 'unique' the lock will be passed to the next task
     */

    public function after(Schedule $schedule): self
    {
        $this->after = $schedule;

        return $this;
    }

    /**
     * get the Jobs for this task
     */

    public function jobs(Lock|array $lock = []): Batch
    {
        return ($batcher = new JobBatcher($this->project, new UploadManager($this->project)))
            ->startAction($this->event, $this->type)
            ->executeJobsBatch(
                $this->getJobs($batcher, $this->prepareLock($lock)),
                $this->after ? [] : $this->prepareLock($lock)
            );
    }

    /**
     * this will return if the current time matches the scheduled time
     */

    public function isTime(): bool
    {
        return (new TimeChecker($this->times, $this->days))->isTime();
    }

    private function getJobs(JobBatcher $batcher, array $lock)
    {
        return $batcher->{'get' . Str::ucfirst($this->event)}($this->type)
            ->when(
                $this->after,
                fn ($collection) => $collection->push(
                    collect([
                        new AssignBatch($this->project->id, $this->after, $lock)
                    ])
                )
            );
    }

    public function __call($method, $arguments)
    {
        if (method_exists($this, $method)) return $this->$method(...$arguments);

        if (Str::of($method)->lower()->startsWith('every')) {

            foreach ($this->GetTimes() as $key => $function) {

                if (Str::contains($method, $key)) return $this->checkAndSetTime($function($method));
            }
        }

        throw new \Exception("{$method} is not a valid method");
    }

    private function addToTimes($key, $value) : self
    {
        $this->times = array_merge(
            $this->times,
            [
                $key => $value
            ]
        );

        return $this;
    }

    private function getTimes() : array
    {
        return [
            'Minutes' => fn ($name) => [$this->prepareTimeString($name, 'minute'), 'Minute'],
            'Hours' => fn ($name) => [$this->prepareTimeString($name, 'hour'), 'Hour'],
            'Minute' => fn ($name) => ['One', 'Minute'],
            'Hour' => fn ($name) => ['One', 'Hour'],
        ];
    }

    private function prepareTimeString($name, $type) : string
    {
        return Str::of($name)
            ->lower()
            ->after('every')
            ->before($type)
            ->__toString();
    }

    private function checkAndSetTime($array) : self
    {
        if (! isset ($this->numbers[Str::lower($array[0])])) throw new \Exception("{$array[0]} is not a supported number");
        $number = $this->numbers[Str::lower($array[0])];

        return $this->addToTimes('everyMinutes', $array[1] == 'Hour' ? $number * 60 : $number);
    }

    private function prepareLock(array|Lock $lock) : array
    {

        if (is_array($lock)) return $lock;

        return [
            'owner'=> $lock->owner(),
            'key' => $this->get('key')
        ];
    }

    private function set(string $name, $value): self
    {
        $this->$name = $value;

        return $this;
    }
}
