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
use RideTimeServer\Exception\UserException;

abstract class BaseEndpoint
{
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
     * @param string $entityClass
     * @param Criteria $searchCriteria
     * @return array
     */
    protected function listEntities(
        string $entityClass,
        Criteria $searchCriteria
    ): array
    {
        $repository = $this->entityManager->getRepository($entityClass);

        // REVIEW: does this fetch all before filtering?
        $entities = $repository->findAll();

        if ($searchCriteria) {
            $entities = (new ArrayCollection($entities))->matching($searchCriteria);
        }

        $result = [];
        /** @var EntityInterface $entity */
        foreach ($entities as $entity) {
            $result[] = $entity->getDetail();
        }

        return $result;
    }

    /**
     * Add an entity to DB. Requires a valid user signed token
     *
     * @param array $data
     * @param User $currentUser
     * @return object // REVIEW: Return Entity?
     */
    public function add(array $data, User $currentUser): object
    {
        $entity = $this->create($data, $currentUser);
        $this->saveEntity($entity);

        return $entity->getDetail();
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
