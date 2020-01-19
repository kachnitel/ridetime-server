<?php
namespace RideTimeServer;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use RideTimeServer\API\Repositories\BaseRepository;
use RideTimeServer\API\Repositories\BaseTrailforksRepository;
use Slim\Container;

class CustomEntityManager extends EntityManagerDecorator
{
    /**
     * @var Container
     */
    protected $container;

    protected $repositoryFactory;

    public function __construct(EntityManagerInterface $wrapped, Container $container)
    {
        parent::__construct($wrapped);
        $this->repositoryFactory = $wrapped->getConfiguration()->getRepositoryFactory();
        $this->container = $container;
    }

    public function getRepository($className): EntityRepository
    {
        $repo = $this->repositoryFactory->getRepository($this, $className);
        if ($repo instanceof BaseTrailforksRepository) {
            $repo->setConnector($this->container->get('trailforks'));
        }
        if ($repo instanceof BaseRepository) {
            $repo->setLogger($this->container->get('logger'));
        }

        return $repo;
    }
}
