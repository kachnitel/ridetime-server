<?php
namespace RideTimeServer\API\Endpoints;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;
use RideTimeServer\Entities\EntityInterface;

class Endpoint
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
     *
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
}
