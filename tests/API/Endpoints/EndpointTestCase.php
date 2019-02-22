<?php
namespace RideTimeServer\Tests\API\Endpoints;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Monolog\Logger;

class EndpointTestCase extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp(): void {
        // Setup Doctrine
        $configuration = Setup::createAnnotationMetadataConfiguration(
            $paths = [__DIR__ . '/entities'],
            $isDevMode = true
        );

        // Setup connection parameters
        $connectionParameters = [
            'dbname' => 'database',
            'user' => 'user',
            'password' => 'password',
            'host' => 'host',
            'driver' => 'pdo_mysql'
        ];

        $this->entityManager = EntityManager::create($connectionParameters, $configuration);
    }

    public function callMethod($obj, string $name, array $args) {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }
}
