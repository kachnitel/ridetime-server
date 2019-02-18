<?php
namespace RideTimeServer\Tests;

use PHPUnit\Framework\TestCase;
use RideTimeServer\Logger;

class UserTest extends TestCase
{
    public function testReturnsMonologInstance()
    {
        $logger = (new Logger())->getLogger([
            'appName' => 'test',
            'logPath' => './test.log'
        ]);

        $this->assertInstanceOf(\Monolog\Logger::class, $logger);
    }
}
