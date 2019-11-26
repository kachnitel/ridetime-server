<?php
namespace RideTimeServer\API\Endpoints\Database;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use RideTimeServer\Entities\EntityInterface;
use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\EntityNotFoundException;
use RideTimeServer\Exception\RTException;
use RideTimeServer\Exception\UserException;

abstract class BaseEndpoint
{
    const ENTITY_CLASS = '';

    /**
     * Doctrine entity manager
     *
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(EntityManagerInterface $entityManager, Logger $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
        if (static::ENTITY_CLASS === '') {
            throw new RTException('ENTITY_CLASS constant must be set in ' . static::class);
        }
    }

    /**
     * Load entity from database
     *
     * @param integer $id
     * @return PrimaryEntity
     */
    public function get(int $id)
    {
        return $this->getEntity(self::ENTITY_CLASS, $id);
    }

    /**
     * @param EntityInterface $entity
     * @return void
     */
    protected function saveEntity(EntityInterface $entity)
    {
        $this->entityManager->persist($entity);
        try {
            $this->entityManager->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            $errorId = uniqid();
            $entityClassName = substr(strrchr(get_class($entity), '\\'), 1);

            $this->logger->addWarning($entityClassName . ' creation failed', [
                'message' => $e->getMessage(),
                'code' => $e->getErrorCode(),
                'errorId' => $errorId
            ]);

            /**
             * TODO: determine the conflicting column
             */
            throw new UserException($entityClassName . ' already exists', 409);
        }
    }

    /**
     * REVIEW: Requires entities to be flushed
     * @param string $entityClass
     * @param integer $id
     * @return EntityInterface
     */
    protected function getEntity(string $entityClass, int $id): EntityInterface
    {
        $entity = $this->entityManager->find($entityClass, $id);

        $this->validateEntity($entity, $id);

        return $entity;
    }

    protected function validateEntity(?EntityInterface $entity, int $id)
    {
        if (empty($entity)) {
            $path = explode('\\', static::ENTITY_CLASS);
            $entityName = array_pop($path);
            $exc = new EntityNotFoundException($entityName . ' ID:' . $id . ' not found', 404);
            $exc->setData(['class' => get_class($this), 'stackTrace' => debug_backtrace()]);

            throw $exc;
        }
    }

    /**
     * @param string $entityClass
     * @param Criteria $searchCriteria
     * @return PrimaryEntity[]
     */
    protected function listEntities(string $entityClass, Criteria $searchCriteria): array
    {
        $entities = $this->entityManager->getRepository($entityClass)->findAll();

        if ($searchCriteria) {
            $entities = (new ArrayCollection($entities))->matching($searchCriteria);
        }

        return $entities->getValues();
    }

    /**
     * Add an entity to DB. Requires a valid user signed token
     *
     * @param array $data
     * @param User $currentUser
     * @return PrimaryEntity
     */
    public function add(array $data, User $currentUser): PrimaryEntity
    {
        $entity = $this->create($data, $currentUser);
        $this->saveEntity($entity);

        return $entity;
    }

    abstract protected function create(array $data, User $currentUser): PrimaryEntity;

    /**
     * Find an entity of type set by static::ENTITY_CLASS by $attribute
     *
     * @param string $attribute
     * @param string $value
     * @return EntityInterface[]
     */
    public function findBy(string $attribute, string $value): array
    {
        return $this->doFindBy([$attribute => $value]);
    }

    /**
     * Find an user by $attribute
     *
     * @param string $attribute
     * @param string $value
     * @return EntityInterface
     */
    public function findOneBy(string $attribute, string $value): EntityInterface
    {
        return $this->doFindBy([$attribute => $value], true);
    }

    protected function doFindBy(array $criteria, bool $findOne = false)
    {
        $method = $findOne ? 'findOneBy' : 'findBy';
        try {
            $result = $this->entityManager->getRepository(static::ENTITY_CLASS)->{$method}($criteria);
        } catch (\Doctrine\ORM\ORMException $e) {
            throw new RTException("Error looking up " . static::ENTITY_CLASS . " by {$criteria[0]} = {$criteria[1]}", 0, $e);
        }
        if (empty($result)) {
            throw new EntityNotFoundException(static::ENTITY_CLASS . " with {$criteria[0]} = {$criteria[1]} not found", 404);
        }
        return $result;
    }
}
