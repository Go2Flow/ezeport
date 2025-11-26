<?php

namespace Go2Flow\Ezport\Instructions\Setters\Tools;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TimeChecker {

    private array $times;

    public function __construct(Collection $times, private ?array $days, private ?Carbon $current = null)
    {
        $this->times = $times->toArray();
        $this->current = ($current ??  Carbon::now())->setTimezone('Europe/Berlin');
    }

    public function isTime() : bool
    {

        if ($this->days && ! in_array($this->current->dayOfWeek, $this->days)) return false;
        if (! $this->checkBetween()) return false;

        if ($this->checkDailyAt() || $this->checkEveryMinutes()) return true;

        return false;
    }

    private function checkEveryMinutes() : bool
    {
        if (isset($this->times['everyMinutes'])) {

            foreach ($this->times['everyMinutes'] as $time) {

                if ($this->minutesSinceStartOfDay($this->current->copy()->setTimezone('Europe/Berlin'))  % $time === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function checkDailyAt() : bool
    {
        if (isset($this->times['dailyAt'])) {

            foreach($this->times['dailyAt'] as $time) {
                if (
                    $this->minutesSinceStartOfDay(Carbon::createFromTimeString($time, 'Europe/Berlin'))
                    ==
                    $this->minutesSinceStartOfDay($this->current->copy()->setTimezone('Europe/Berlin'))
                ) return true;
            }


        }

        return false;
    }

    private function checkBetween() : bool
    {
        if (isset($this->times['between'])) {

            if (! $this->current->between(
                Carbon::createFromTimeString($this->times['between'][0]['start'], 'Europe/Berlin'),
                Carbon::createFromTimeString($this->times['between'][0]['end'], 'Europe/Berlin')
            )) return false;
        }

        return true;
    }

    private function minutesSinceStartOfDay(Carbon $time) : int
    {
        return $time->diffInMinutes(
            $time->copy()
            ->setTimezone('Europe/Berlin')
            ->startOfDay()
        );
    }
}
