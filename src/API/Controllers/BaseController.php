<?php
namespace RideTimeServer\API\Controllers;

use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManagerInterface;
use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\Route;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Entities\User;
use RideTimeServer\API\Repositories\EventRepository;
use RideTimeServer\API\Repositories\LocationRepository;
use RideTimeServer\API\Repositories\RouteRepository;
use RideTimeServer\API\Repositories\TrailRepository;
use RideTimeServer\API\Repositories\UserRepository;

abstract class BaseController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function extractDetails(array $entities): array
    {
        return array_map(function (PrimaryEntity $entity) {
            return $entity->getDetail();
        }, $entities);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get('entityManager');
    }

    protected function getEventRepository(): EventRepository
    {
        return $this->getEntityManager()
            ->getRepository(Event::class);
    }

    protected function getUserRepository(): UserRepository
    {
        return $this->getEntityManager()
            ->getRepository(User::class);
    }

    protected function getLocationRepository(): LocationRepository
    {
        return $this->getEntityManager()
            ->getRepository(Location::class);
    }

    protected function getRouteRepository(): RouteRepository
    {
        return $this->getEntityManager()
            ->getRepository(Route::class);
    }

    protected function getTrailRepository(): TrailRepository
    {
        return $this->getEntityManager()
            ->getRepository(Trail::class);
    }
}
