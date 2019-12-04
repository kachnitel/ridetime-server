<?php
namespace RideTimeServer\Tests\Entities;

use PHPUnit\Framework\TestCase;
use RideTimeServer\Entities\PrimaryEntity;

class EntityTestCase extends TestCase
{
    public function set(PrimaryEntity $entity, $value, string $propertyName = 'id')
    {
        $class = new \ReflectionClass($entity);
        $property = $class->getProperty($propertyName);
        $property->setAccessible(true);

        $property->setValue($entity, $value);
    }
}
