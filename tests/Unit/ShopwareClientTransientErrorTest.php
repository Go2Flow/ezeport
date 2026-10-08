<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Connectors\ShopwareSix\Client;
use PHPUnit\Framework\TestCase;

class ShopwareClientTransientErrorTest extends TestCase
{
    public function test_deadlocks_are_transient(): void
    {
        $this->assertTrue(Client::isTransientDatabaseError('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock'));
    }

    public function test_lost_savepoint_after_a_deadlock_is_transient(): void
    {
        $this->assertTrue(Client::isTransientDatabaseError(
            '{"code":"1305","status":"500","detail":"An exception occurred while executing a query: SQLSTATE[42000]: Syntax error or access violation: 1305 SAVEPOINT DOCTRINE_2 does not exist"}'
        ));
    }

    public function test_constraint_violations_are_not_retried(): void
    {
        $this->assertFalse(Client::isTransientDatabaseError(
            'SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row'
        ));
    }
}
