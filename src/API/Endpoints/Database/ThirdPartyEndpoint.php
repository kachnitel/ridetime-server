<?php
namespace RideTimeServer\API\Endpoints\Database;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use RideTimeServer\API\Connectors\TrailforksConnector;
use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\PrimaryEntityInterface;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\RTException;
use RideTimeServer\Exception\EntityNotFoundException;
use RideTimeServer\API\Repositories\RemoteSourceRepositoryInterface;
use RideTimeServer\Entities\EntityInterface;

abstract class ThirdPartyEndpoint extends BaseEndpoint
{
    const ENTITY_CLASS = '';

    /**
     * @var TrailforksConnector
     */
    protected $tfConnector;

    public function __construct(EntityManagerInterface $entityManager, Logger $logger, TrailforksConnector $tfConnector)
    {
        parent::__construct($entityManager, $logger);
        $this->tfConnector = $tfConnector;
        $entityManager->getRepository(static::ENTITY_CLASS)->setConnector($tfConnector);
    }

    /**
     * @param integer $entityId
     * @return PrimaryEntity
     */
    public function get(int $entityId): PrimaryEntity
    {
        return $this->getEntity(static::ENTITY_CLASS, $entityId);
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

        if (empty($entity)) {
            $path = explode('\\', $entityClass);
            $entityName = array_pop($path);
            $exc = new EntityNotFoundException($entityName . ' ID:' . $id . ' not found', 404);
            $exc->setData(['class' => get_class($this), 'stackTrace' => debug_backtrace()]);

            throw $exc;
        }

        return $entity;
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
     * @deprecated
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
    // abstract protected function populateEntity($entity, object $data): PrimaryEntity;

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
