<?php
namespace RideTimeServer\API\Repositories;

use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\API\Connectors\TrailforksConnector;
use RideTimeServer\Exception\EntityNotFoundException;

abstract class BaseTrailforksRepository extends BaseRepository
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
     * @param integer $remoteId
     * @return PrimaryEntity
     */
    public function findRemote(int $remoteId): PrimaryEntity
    {
        $result = $this->findOneBy(['remoteId' => $remoteId, 'source' => 'trailforks']);

        if ($result !== null) {
            return $result;
        }

        // REVIEW: Check un-flushed entities
        if ($insertions = $this->getEntityManager()->getUnitOfWork()->getScheduledEntityInsertions()) {
            foreach ($insertions as $entity) {
                if (get_class($entity) === $this->getEntityName() && $entity->getRemoteId() === $remoteId) {
                    return $entity;
                }
            }
        }

        $path = explode('\\', $this->getEntityName());
        $entityShortName = array_pop($path);
        $connectorMethod = 'get' . $entityShortName;
        $data = $this->connector->{$connectorMethod}($remoteId, static::API_FIELDS);
        if (!$data) {
            throw new EntityNotFoundException("{$entityShortName} ID: {$remoteId} not found at API!", 404);
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
        $remoteId = $data->{$this->getIdField()};
        /** @var PrimaryEntity $entity */
        $entity = $this->findOneBy([
            'remoteId' => $remoteId,
            'source' => 'trailforks'
        ]) ?? new $entityClass();
        $entity = $this->populateEntity($entity, $data);
        $entity->setRemoteId($remoteId);
        $entity->setSource('trailforks');
        $this->getEntityManager()->persist($entity);
        $this->updatedCounter++;

        return $entity;
    }

    /**
     * @return string
     */
    protected function getIdField()
    {
        return static::API_ID_FIELD;
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
