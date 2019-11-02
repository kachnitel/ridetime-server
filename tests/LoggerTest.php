<?php
namespace RideTimeServer\Tests;

use PHPUnit\Framework\TestCase;
use RideTimeServer\Logger;

class LoggerTest extends TestCase
{
    public function testReturnsMonologInstance()
    {
        $logger = (new Logger())->getLogger([
            'appName' => 'test',
            'log' => ['path' => './test.log']
        ]);

        $this->assertInstanceOf(\Monolog\Logger::class, $logger);
    }
}
