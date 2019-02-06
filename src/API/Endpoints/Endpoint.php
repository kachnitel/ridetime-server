<?php
namespace RideTimeServer\API\Endpoints;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use RideTimeServer\Entities\EntityInterface;

abstract class Endpoint
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
            throw new \Exception('Error: ' . $entityClassName . ' already exists', 409);
        }
    }

    /**
     * @param string $entityClass
     * @param integer $id
     * @return EntityInterface
     */
    protected function getEntity(string $entityClass, int $id): EntityInterface
    {
        $entity = $this->entityManager->find($entityClass, $id);

        if (empty($entity)) {
            throw new \Exception($entityClass . ' ID:' . $id . ' not found', 404);
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
    protected function listEntities(string $entityClass, callable $entityExtractor): array
    {
        $entities = $this->entityManager->getRepository($entityClass)->findAll();

        $result = [];
        foreach ($entities as $entity) {
            $result[] = $entityExtractor($entity);
        }

        return $result;
    }
}
