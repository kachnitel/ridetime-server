<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\PrimaryEntityInterface;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\RTException;

abstract class ThirdPartyEndpoint extends BaseEndpoint
{
    /**
     * TODO: ...before creating RouteEndpoint that uses the same
     * REVIEW: use child class const for the "Entity::class" rather than a string param here?
     *
     * @param string $class
     * @param array $items
     * @return object[]
     */
    public function addMultiple(string $class, array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $entity = $this->upsert($class, $item);
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
    protected function upsert(string $class, object $data)
    {
        $entity = $this->entityManager->find($class, $data->id) ?? new $class();
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
