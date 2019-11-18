<?php
namespace RideTimeServer\Tests\API;

use RideTimeServer\Tests\RTTestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\Setup;
use Monolog\Logger;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\EntityInterface;

/**
 * Sets up an EntityManager instance using a test database.
 * (TODO: doc. DB creation)
 * Cleans up created entities in tearDown.
 */
class APITestCase extends RTTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    protected function setUp(): void
    {
        // Setup Doctrine
        $configuration = Setup::createAnnotationMetadataConfiguration(
            $paths = [__DIR__ . '/../../entities'],
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
        $this->entityManager->clear();
        $this->entityManager->flush();
    }

    private function loadTestSecrets(): object
    {
        $file = __DIR__ . '/../../.secrets.test.json';
        $contents = file_get_contents($file);
        $decoded = json_decode($contents);

        return $decoded;
    }

    /**
     * TODO: fill required values / use ep::createUser?
     *
     * @param [type] $id
     * @return User
     */
    protected function generateUser($id = null): User
    {
        /** @var User $user */
        $user = $this->generateEntity(User::class, $id);

        $name = uniqid('Joe');
        $user->applyProperties([
            // TODO:
            'name' => $name,
            'email' => $name . '@provider.place'
        ]);
        $user->setAuthId(uniqid($name . '-'));

        return $user;
    }

    protected function generateEvent($id = null, User $user = null): Event
    {
        /** @var Event $event */
        $event = $this->generateEntity(Event::class, $id);
        $event->setTitle(uniqid('Event'));
        $event->setDate(new \DateTime('2 hours'));
        $event->setDifficulty(rand(0, 4));
        $event->setTerrain('trail');
        if ($user === null) {
            $user = $this->generateUser();
            $this->entityManager->persist($user);
        }
        $event->setCreatedBy($user);

        return $event;
    }

    protected function generateEntity(string $class, $id = null): EntityInterface
    {
        $reflection = new \ReflectionClass($class);
        $entity = $reflection->newInstance();

        // Set private id
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id ?? rand(1,100));

        return $entity;
    }

    protected function getLogger(): Logger
    {
        return new Logger(static::class);
    }
}
