<?php
namespace RideTimeServer\API\Endpoints\Database;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\ArrayCollection;
use Monolog\Logger;
use RideTimeServer\Entities\EntityInterface;
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

    public function __construct(EntityManager $entityManager, Logger $logger)
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
            throw new EntityNotFoundException($entityName . ' ID:' . $id . ' not found', 404);
        }

        return $entity;
    }

    /**
     * Second argument should accept EntityInterface as a parameter
     * and return an object to return in the Response
     *
     * @param string $entityClass
     * @param callable $entityExtractor
     * @return array
     */
    protected function listEntities(
        string $entityClass,
        callable $entityExtractor,
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
        foreach ($entities as $entity) {
            $result[] = $entityExtractor($entity);
        }

        return $result;
    }
}
