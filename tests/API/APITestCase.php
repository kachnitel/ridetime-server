<?php
namespace RideTimeServer\Tests\API;

use RideTimeServer\Tests\RTTestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\Setup;
use Monolog\Logger;
use RideTimeServer\API\Connectors\TrailforksConnector;
use RideTimeServer\CustomEntityManager;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\EntityInterface;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Exception\RTException;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Stream;

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

    /**
     * @var User
     */
    protected $currentUser;

    protected function setUp(): void
    {
        // Setup Doctrine
        $configuration = Setup::createAnnotationMetadataConfiguration(
            [__DIR__ . '/../../src/Entities'], // paths
            true // isDevMode
        );

        $secrets = $this->loadTestSecrets();
        // Setup connection parameters
        $connectionParameters = [
            'dbname' => $secrets['db']['database'],
            'user' => $secrets['db']['user'],
            'password' => $secrets['db']['password'],
            'host' => $secrets['db']['host'],
            'driver' => 'pdo_mysql'
        ];

        $this->currentUser = $this->generateUser(null, false);

        $container = $this->getContainer($secrets);

        $this->entityManager = new CustomEntityManager(
            EntityManager::create($connectionParameters, $configuration),
            $container
        );

        try {
            $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
            foreach ($metadata as $type) {
                $entities = $this->entityManager->getRepository($type->getName())->findAll();
                foreach ($entities as $entity) {
                    $this->entityManager->remove($entity);
                }
            }
            $this->entityManager->persist($this->currentUser);
            $this->entityManager->flush();
        } catch (\Exception $exception) {
            throw new RTException('Error cleaning up database in setUp: ' . $exception->getMessage(), 0, $exception);
        }
    }

    protected function getContainer($secrets)
    {
        $logger = $this->getLogger();
        return new Container([
            'logger' => $logger,
            'trailforks' => new TrailforksConnector(
                $secrets['trailforks'],
                $logger
            ),
            'request' => $this->getRequest('GET')
                ->withAttribute('currentUser', $this->currentUser)
        ]);
    }

    private function loadTestSecrets(): array
    {
        $file = __DIR__ . '/../../.secrets.test.json';
        $contents = file_get_contents($file);
        $decoded = json_decode($contents, true);

        return $decoded;
    }

    /**
     * @param int $id
     * @return User
     */
    protected function generateUser(int $id = null, bool $persist = true): User
    {
        /** @var User $user */
        $user = $this->generateEntity(User::class, $id, $persist);
        $name = $user->getName();
        $user->applyProperties((object) [
            // TODO:
            'email' => $name . '@provider.place'
        ]);
        $user->setAuthId(uniqid($name . '-'));

        return $user;
    }

    /**
     * @param integer $id
     * @param User $createdBy
     * @param Location $location
     * @param User[] $members
     * @return Event
     */
    protected function generateEvent(
        int $id = null,
        User $createdBy = null,
        Location $location = null,
        $members = []
    ): Event
    {
        /** @var Event $event */
        $event = $this->generateEntity(Event::class, $id);
        $event->setDate(new \DateTime('2 hours'));
        $event->setDifficulty(rand(0, 4));
        $event->setTerrain('trail');
        if ($createdBy === null) {
            $createdBy = $this->generateUser();
        }
        $members[] = $createdBy;
        $event->setCreatedBy($createdBy);
        if ($location === null) {
            $location = $this->generateLocation();
        }
        $event->setLocation($location);
        foreach ($members as $member) {
            $membership = new EventMember();
            $membership->setEvent($event);
            $membership->setUser($member);
            $membership->setStatus(Event::STATUS_CONFIRMED);
            $event->addMember($membership);
        }

        return $event;
    }

    protected function generateLocation(int $id = null): Location
    {
        /** @var Location $location */
        $location = $this->generateEntity(Location::class, $id);
        $location->setGpsLat(rand(0, 999999999) / 1000000);
        $location->setGpsLon(rand(0, 999999999) / 1000000);
        $numbers = range(0, 10);
        shuffle($numbers);
        $location->setDifficulties(array_slice($numbers, 0, 5));
        $location->setAlias('location-alias-' . $location->getId());
        return $location;
    }

    protected function generateTrail(int $id = null): Trail
    {
        /** @var Trail $trail */
        $trail = $this->generateEntity(Trail::class, $id);
        $trail->setDifficulty(rand(0, 10));
        $trail->setDescription('TrailDescription' . $trail->getId());
        $trail->setAlias('trail-alias-' . $trail->getId());
        return $trail;
    }

    /**
     * @param string $class
     * @param integer $id
     * @param bool $persist
     * @return EntityInterface
     */
    protected function generateEntity(string $class, int $id = null, bool $persist = true): EntityInterface
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

        if ($persist) {
            $this->entityManager->persist($entity);
        }

        return $entity;
    }

    /**
     * Returns Mocked $class instance which replaces 'remoteFilter()'
     *   return value with its arguments
     * @param string $class Class that implements RemoteSourceRepositoryInterface
     * @return MockObject|RemoteSourceRepositoryInterface
     */
    protected function getRepoMockRemoteFilter(string $class)
    {
        /** @var MockObject|RemoteSourceRepositoryInterface $mockRepo */
        $mockRepo = $this->getMockBuilder($class)
            ->setMethods(['remoteFilter'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockRepo->expects($this->exactly(1))
            ->method('remoteFilter')
            ->will(
                $this->returnCallback(function () {
                    return func_get_args();
                 })
            );

        return $mockRepo;
    }

    public function getRequest(string $method): Request
    {
        return new Request(
            $method,
            new Uri('http', 'www.test.ca'),
            new Headers([]),
            [],
            [],
            new Stream(fopen('php://memory', 'r+'))
        );
    }

    protected function getLogger(): Logger
    {
        return new Logger(static::class);
    }
}
