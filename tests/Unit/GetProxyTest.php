<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Instructions\Getters\GetProxy;
use PHPUnit\Framework\TestCase;

class GetProxyTest extends TestCase
{
    // --- basic invocation ---

    public function test_invoke_calls_callback_with_project(): void
    {
        $proxy = new GetProxy(fn ($project) => (object) ['type' => $project]);

        $result = $proxy('myProject');

        $this->assertEquals('myProject', $result->type);
    }

    // --- method chaining ---

    public function test_call_records_methods_and_replays_on_invoke(): void
    {
        $obj = new class {
            public array $calls = [];

            public function setName(string $name): self
            {
                $this->calls[] = ['setName', $name];
                return $this;
            }

            public function setAge(int $age): self
            {
                $this->calls[] = ['setAge', $age];
                return $this;
            }
        };

        $proxy = new GetProxy(fn ($project) => $obj);
        $proxy->setName('Test');
        $proxy->setAge(25);

        $result = $proxy('project');

        $this->assertEquals([
            ['setName', 'Test'],
            ['setAge', 25],
        ], $result->calls);
    }

    public function test_call_returns_self_for_chaining(): void
    {
        $proxy = new GetProxy(fn ($p) => new \stdClass());

        $result = $proxy->someMethod('arg');

        $this->assertSame($proxy, $result);
    }

    public function test_empty_methods_just_invokes_callback(): void
    {
        $called = false;
        $proxy = new GetProxy(function ($project) use (&$called) {
            $called = true;
            return 'result';
        });

        $result = $proxy('proj');

        $this->assertTrue($called);
        $this->assertEquals('result', $result);
    }

    public function test_methods_replayed_in_order(): void
    {
        $order = new \ArrayObject();

        $obj = new class($order) {
            private \ArrayObject $order;

            public function __construct(\ArrayObject $order)
            {
                $this->order = $order;
            }

            public function first(): void
            {
                $this->order[] = 'first';
            }

            public function second(): void
            {
                $this->order[] = 'second';
            }

            public function third(): void
            {
                $this->order[] = 'third';
            }
        };

        $proxy = new GetProxy(function ($project) use ($obj) {
            return $obj;
        });

        $proxy->first();
        $proxy->second();
        $proxy->third();

        $proxy('proj');

        $this->assertEquals(['first', 'second', 'third'], $order->getArrayCopy());
    }

    // --- chaining with method arguments ---

    public function test_methods_replay_with_correct_arguments(): void
    {
        $obj = new class {
            public array $results = [];

            public function chunk(int $size): self
            {
                $this->results['chunk'] = $size;
                return $this;
            }

            public function dropFields(): self
            {
                $this->results['dropped'] = true;
                return $this;
            }

            public function config(array $config): self
            {
                $this->results['config'] = $config;
                return $this;
            }
        };

        $proxy = new GetProxy(fn ($p) => $obj);
        $proxy->chunk(50);
        $proxy->dropFields();
        $proxy->config(['key' => 'value']);

        $result = $proxy('proj');

        $this->assertEquals(50, $result->results['chunk']);
        $this->assertTrue($result->results['dropped']);
        $this->assertEquals(['key' => 'value'], $result->results['config']);
    }
}
