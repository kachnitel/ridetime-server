<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\PrimaryEntityInterface;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\RTException;
use RideTimeServer\API\Repositories\RemoteSourceRepositoryInterface;
use RideTimeServer\Entities\EntityInterface;

abstract class ThirdPartyEndpoint extends BaseEndpoint
{
    /**
     * @param integer $entityId
     * @return PrimaryEntity
     */
    public function get(int $entityId): PrimaryEntity
    {
        return $this->getEntity(static::ENTITY_CLASS, $entityId);
    }

    /**
     * @param array|null $ids
     * @return array
     */
    public function list(?array $ids = null): array
    {
        throw new RTException(static::ENTITY_CLASS . '::list() method not implemented', 501);
        return []; // Satisfy VSCode
    }

    /**
     * REVIEW: Requires entities to be flushed
     * @param string $entityClass
     * @param integer $id
     * @return EntityInterface
     */
    protected function getEntity(string $entityClass, int $id): EntityInterface
    {
        $repo = $this->entityManager->getRepository($entityClass);
        if (!$repo instanceof RemoteSourceRepositoryInterface) {
            throw new RTException(
                'ThirdPartyEndpoint requires RemoteSourceRepositoryInterface, ' . get_class($repo) . ' supplied'
            );
        }

        $entity = $repo->findWithFallback($id);

        $this->validateEntity($entity, $id);

        return $entity;
    }

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
