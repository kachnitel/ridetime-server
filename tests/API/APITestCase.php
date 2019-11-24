<?php
namespace RideTimeServer\Tests\API;

use RideTimeServer\Tests\RTTestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Monolog\Logger;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\EntityInterface;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\Trail;

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
            $paths = [__DIR__ . '/../../src/Entities'],
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

        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        foreach ($metadata as $type) {
            $entities = $this->entityManager->getRepository($type->getName())->findAll();
            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
            }
        }
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
     * @param int $id
     * @return User
     */
    protected function generateUser(int $id = null): User
    {
        /** @var User $user */
        $user = $this->generateEntity(User::class, $id);
        $name = $user->getName();
        $user->applyProperties([
            // TODO:
            'email' => $name . '@provider.place'
        ]);
        $user->setAuthId(uniqid($name . '-'));

        return $user;
    }

    protected function generateEvent(int $id = null, User $user = null): Event
    {
        /** @var Event $event */
        $event = $this->generateEntity(Event::class, $id);
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

    protected function generateLocation(int $id = null): Location
    {
        /** @var Location $location */
        $location = $this->generateEntity(Location::class, $id);
        $location->setGpsLat(rand(0, 999999999) / 1000000);
        $location->setGpsLon(rand(0, 999999999) / 1000000);
        $location->setDifficulties(array_rand(Trail::DIFFICULTIES, rand(2,5)));
        return $location;
    }

    protected function generateTrail(int $id = null): Trail
    {
        /** @var Trail $trail */
        $trail = $this->generateEntity(Trail::class, $id);
        $trail->setDifficulty(array_rand(Trail::DIFFICULTIES));
        $trail->setDescription('TrailDescription' . $trail->getId());
        return $trail;
    }

    /**
     * @param string $class
     * @param integer $id
     * @param string $nameField // Field in which an entity name is stored (user->name, event->title)
     * @return EntityInterface
     */
    protected function generateEntity(string $class, int $id = null): EntityInterface
    {
        $reflection = new \ReflectionClass($class);
        $entity = $reflection->newInstance();

        // Set private id
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($entity, $id ?? mt_rand());

        $path = explode('\\', $class);
        $entityType = array_pop($path);
        $generatedNameTitle = $entityType . '_' . $entity->getId();
        method_exists($entity, 'setName')
            ? $entity->setName($generatedNameTitle)
            : $entity->setTitle($generatedNameTitle);

        return $entity;
    }

    protected function getLogger(): Logger
    {
        return new Logger(static::class);
    }
}
