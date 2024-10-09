<?php

namespace Go2Flow\Ezport\Instructions\Setters\Tools;

use Illuminate\Support\Carbon;

class TimeChecker {

    public function __construct(private array $times, private ?array $days, private ?Carbon $current = null)
    {
        $this->current = $current ??  Carbon::now();
    }

    public function isTime() : bool
    {

        if ($this->days && ! in_array($this->current->dayOfWeek, $this->days)) return false;
        if (! $this->checkBetween()) return false;

        foreach (['DailyAt', 'EveryMinutes'] as $time) {
            if ($this->{'check' . $time}()) return true;
        }

        return false;
    }

    private function checkEveryMinutes() : bool
    {
        if (isset($this->times['everyMinutes'])) {

            $minutes = $this->minutesSinceStartOfDay($this->current->setTimezone('Europe/Berlin'));

            if ($minutes == 0) $minutes = 1440;

            return ! ($this->minutesSinceStartOfDay($this->current->setTimezone('Europe/Berlin'))  % $this->times['everyMinutes']);
        }

        return false;
    }

    private function checkDailyAt() : bool
    {

        if (isset($this->times['dailyAt'])) {

            if ($this->minutesSinceStartOfDay(Carbon::createFromTimeString($this->times['dailyAt'], 'Europe/Berlin'))
                ==
                $this->minutesSinceStartOfDay($this->current->setTimezone('Europe/Berlin'))
            ) return true;
        }

        return false;
    }

    private function checkBetween() : bool
    {
        if (isset($this->times['between'])) {

            if (! $this->current->between(
                Carbon::createFromTimeString($this->times['between']['start'], 'Europe/Berlin'),
                Carbon::createFromTimeString($this->times['between']['end'], 'Europe/Berlin')
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
