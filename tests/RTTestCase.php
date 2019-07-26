<?php
namespace RideTimeServer\Tests;

use PHPUnit\Framework\TestCase;

class RTTestCase extends TestCase
{
    public function callMethod($obj, string $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }
}