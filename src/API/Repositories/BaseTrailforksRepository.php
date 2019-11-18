<?php
namespace RideTimeServer\API\Repositories;

use Doctrine\ORM\EntityRepository;
use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\API\Connectors\TrailforksConnector;
use RideTimeServer\Exception\EntityNotFoundException;

abstract class BaseTrailforksRepository extends EntityRepository
{
    /**
     * Optional filter for api fields
     */
    const API_FIELDS = [];

    /**
     * ID field returned from API response
     */
    const API_ID_FIELD = '';

    /**
     * @var TrailforksConnector
     */
    protected $connector;

    /**
     * Indicates whether a flush is needed when finished
     *
     * @var integer
     */
    protected $updatedCounter = 0;

    public function setConnector(TrailforksConnector $trailforksConnector)
    {
        $this->connector = $trailforksConnector;
    }

    public function __destruct()
    {
        if ($this->updatedCounter > 0) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Look into the DB for entity, with fallback to Trailforks if not found in DB
     * Adds to DB if found at API
     *
     * @param integer $id
     * @return PrimaryEntity
     */
    public function findWithFallback(int $id): PrimaryEntity
    {
        $result = $this->find($id);

        if ($result !== null) {
            return $result;
        }

        $path = explode('\\', $this->getEntityName());
        $entityShortName = array_pop($path);
        $connectorMethod = 'get' . $entityShortName;
        $data = $this->connector->{$connectorMethod}($id, static::API_FIELDS);
        if (!$data) {
            throw new EntityNotFoundException("{$entityShortName} ID: {$id} not found at API!", 404);
        }
        return $this->upsert($data);
    }

    /**
     * Create new item or update existing with new data
     *
     * @param string $class
     * @param object $data
     * @return PrimaryEntity
     */
    public function upsert(object $data): PrimaryEntity
    {
        $entityClass = $this->getClassName();
        $entity = $this->getEntityManager()->find(
            $entityClass,
            $data->{static::API_ID_FIELD}
        ) ?? new $entityClass();
        $this->populateEntity($entity, $data);

        $this->getEntityManager()->persist($entity);
        $this->updatedCounter++;

        return $entity;
    }

    /**
     * Populate $entity with $data
     *
     * @param PrimaryEntity $entity
     * @param object $data
     * @return PrimaryEntity
     */
    abstract protected function populateEntity(PrimaryEntity $entity, object $data): PrimaryEntity;
}
