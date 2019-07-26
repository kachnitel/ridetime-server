<?php
namespace RideTimeServer\Tests\API;

use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;

/**
 * Sets up an EntityManager instance using a test database.
 * (TODO: doc. DB creation)
 * Cleans up created entities in tearDown.
 */
class APITestCase extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var array
     */
    protected $entities = [];

    protected function setUp(): void
    {
        // Setup Doctrine
        $configuration = Setup::createAnnotationMetadataConfiguration(
            $paths = [__DIR__ . '/entities'],
            $isDevMode = true
        );

        $secrets = $this->loadTestSecrets();
        // Setup connection parameters
        $connectionParameters = [
            'dbname' => $secrets->db->database,
            'user' => $secrets->db->user,
            'password' => $secrets->db->password,
            'host' => $secrets->db->host,
            'driver' => 'pdo_mysql'
        ];

        $this->entityManager = EntityManager::create($connectionParameters, $configuration);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        foreach ($this->entities as $entity) {
            if ($this->entityManager->contains($entity)) {
                $this->entityManager->remove($entity);
            } else {
                $type = get_class($entity);
                $id = method_exists($entity, 'getId') ? $entity->getId() : null;
                $this->addWarning("Trying to remove entity '{$type}'" . ($id ? " with ID '{$id}'" : ''));
            }
        }
        $this->entityManager->flush();
    }

    public function callMethod($obj, string $name, array $args)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs($obj, $args);
    }

    private function loadTestSecrets(): object
    {
        $file = __DIR__ . '/../../.secrets.test.json';
        $contents = file_get_contents($file);
        $decoded = json_decode($contents);

        return $decoded;
    }
}
