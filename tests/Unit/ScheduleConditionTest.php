<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Instructions\Setters\Set;
use PHPUnit\Framework\TestCase;

class ScheduleConditionTest extends TestCase
{
    public function test_a_task_without_condition_may_start(): void
    {
        $this->assertTrue(Set::Schedule()->import()->conditionMet());
    }

    public function test_the_condition_decides_whether_the_task_starts(): void
    {
        $waiting = false;
        $schedule = Set::Schedule()->import()->onlyWhen(function () use (&$waiting) {
            return $waiting;
        });

        $this->assertFalse($schedule->conditionMet());

        $waiting = true;

        $this->assertTrue($schedule->conditionMet());
    }

    public function test_only_when_returns_the_schedule(): void
    {
        $schedule = Set::Schedule();

        $this->assertSame($schedule, $schedule->onlyWhen(fn () => true));
    }
}
