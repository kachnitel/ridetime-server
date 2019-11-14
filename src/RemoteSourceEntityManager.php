<?php
namespace RideTimeServer;

use Doctrine\ORM\Decorator\EntityManagerDecorator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use RideTimeServer\API\Connectors\TrailforksConnector;
use RideTimeServer\API\Repositories\BaseTrailforksRepository;

class RemoteSourceEntityManager extends EntityManagerDecorator
{
    /**
     * @var TrailforksConnector
     */
    protected $trailforksConnector;

    protected $repositoryFactory;

    public function __construct(EntityManagerInterface $wrapped, TrailforksConnector $trailforksConnector)
    {
        parent::__construct($wrapped);
        $this->repositoryFactory = $wrapped->getConfiguration()->getRepositoryFactory();
        $this->trailforksConnector = $trailforksConnector;
    }

    public function getRepository($className): EntityRepository
    {
        $repo = $this->repositoryFactory->getRepository($this, $className);
        if ($repo instanceof BaseTrailforksRepository) {
            $repo->setConnector($this->trailforksConnector);
        }

        return $repo;
    }
}
