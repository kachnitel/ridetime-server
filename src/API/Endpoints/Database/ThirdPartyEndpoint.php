<?php
namespace RideTimeServer\API\Endpoints\Database;

use Doctrine\ORM\EntityManager;
use RideTimeServer\API\Endpoints\RestApi\TrailforksEndpoint;
use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\PrimaryEntityInterface;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\RTException;
use \Monolog\Logger;

abstract class ThirdPartyEndpoint extends BaseEndpoint
{
    const ENTITY_CLASS = null;

    /**
     * @var TrailforksEndpoint
     */
    protected $TfEndpoint;

    public function __construct(EntityManager $entityManager, Logger $logger, TrailforksEndpoint $TfEndpoint)
    {
        parent::__construct($entityManager, $logger);
        $this->TfEndpoint = $TfEndpoint;
    }

    /**
     * @param object[] $items
     * @return object[]
     */
    public function addMultiple(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $entity = $this->upsert($item);
            $result[] = $entity->getDetail();
        }
        $this->entityManager->flush();

        return $result;
    }

    /**
     * Create new item or update existing with new data
     *
     * @param string $class
     * @param object $data
     * @return PrimaryEntity
     */
    public function upsert(object $data)
    {
        $entityClass = static::ENTITY_CLASS;
        $entity = $this->entityManager->find($entityClass, $data->id) ?? new $entityClass();
        $this->populateEntity($entity, $data);

        $this->entityManager->persist($entity);

        return $entity;
    }

    /**
     * Update entity with $data
     *
     * @param [type] $entity
     * @param object $data
     * @return PrimaryEntity
     */
    abstract protected function populateEntity($entity, object $data): PrimaryEntity;

    /**
     * TODO:
     *  data coming from app/user should also be objects(not arrays) like from APIs
     *
     * @param array $data
     * @param User $currentUser
     * @return PrimaryEntityInterface
     */
    protected function create(array $data, User $currentUser): PrimaryEntity
    {
        throw new RTException("Manually creating 3p endpoints not implemented", 501);
        return new PrimaryEntity();
    }
}
