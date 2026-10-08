<?php

namespace Go2Flow\Ezport\Tests\Unit;

use Go2Flow\Ezport\Connectors\Ftp\Api;
use PHPUnit\Framework\TestCase;

class FtpDiskConfigTest extends TestCase
{
    private function connector(mixed $properties = null): array
    {
        return [
            'host' => 'ftp.example.com',
            'username' => 'user',
            'password' => 'secret',
            'project_id' => 1,
            'properties' => $properties,
        ];
    }

    public function test_without_properties_has_no_root(): void
    {
        $config = Api::diskConfig($this->connector());

        $this->assertSame('ftp', $config['driver']);
        $this->assertSame('ftp.example.com', $config['host']);
        $this->assertSame('user', $config['username']);
        $this->assertSame('secret', $config['password']);
        $this->assertArrayNotHasKey('root', $config);
    }

    public function test_root_from_collection_properties(): void
    {
        $config = Api::diskConfig($this->connector(collect(['root' => 'stage'])));

        $this->assertSame('stage', $config['root']);
    }

    public function test_root_from_array_properties_is_trimmed(): void
    {
        $config = Api::diskConfig($this->connector(['root' => '/stage/']));

        $this->assertSame('stage', $config['root']);
    }

    public function test_empty_root_is_ignored(): void
    {
        $config = Api::diskConfig($this->connector(collect(['root' => ''])));

        $this->assertArrayNotHasKey('root', $config);
    }
}
