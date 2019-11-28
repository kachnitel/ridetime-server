<?php
namespace RideTimeServer\API\Controllers;

use Psr\Container\ContainerInterface;
use RideTimeServer\Entities\PrimaryEntity;
use Doctrine\ORM\EntityManagerInterface;

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
        return array_map(function(PrimaryEntity $entity) {
            return $entity->getDetail();
        }, $entities);
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get('entityManager');
    }
}
