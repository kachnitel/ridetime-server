<?php
namespace RideTimeServer\API\Repositories;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityRepository;
use RideTimeServer\Entities\EntityInterface;

class BaseRepository extends EntityRepository
{
    /**
     * @param EntityInterface $entity
     * @return void
     */
    protected function saveEntity(EntityInterface $entity)
    {
        $this->getEntityManager()->persist($entity);
        try {
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException $e) {
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
}
