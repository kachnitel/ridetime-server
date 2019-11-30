<?php
namespace RideTimeServer\API\Repositories;

use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityRepository;
use Monolog\Logger;
use RideTimeServer\Entities\EntityInterface;
use RideTimeServer\Entities\PrimaryEntityInterface;
use RideTimeServer\Exception\EntityNotFoundException;
use RideTimeServer\Exception\UserException;

abstract class BaseRepository extends EntityRepository
{
    /**
     * @var Logger
     */
    protected $logger;

    public function get(int $id): PrimaryEntityInterface
    {
        $entity = $this->find($id);

        if (empty($entity)) {
            $exc = new EntityNotFoundException($this->getClassShortName() . ' ID:' . $id . ' not found', 404);
            $exc->setData(['class' => get_class($this), 'stackTrace' => debug_backtrace()]);

            throw $exc;
        }

        return $entity;
    }

    /**
     * @param array $ids
     * @return array
     */
    public function list(array $ids = null): array
    {
        if (!$ids) {
            return $this->findAll();
        }

        $criteria = Criteria::create()->where(Criteria::expr()->in(
            'id',
            array_map('intval', $ids)
        ));

        return $this->matching($criteria)->getValues();
    }

    /**
     * @param EntityInterface $entity
     * @return void
     */
    public function saveEntity(EntityInterface $entity)
    {
        $this->getEntityManager()->persist($entity);
        try {
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException $e) {
            $errorId = uniqid();

            $this->logger->addWarning($this->getClassShortName() . ' creation failed', [
                'message' => $e->getMessage(),
                'code' => $e->getErrorCode(),
                'errorId' => $errorId
            ]);

            /**
             * TODO: determine the conflicting column
             */
            throw new UserException($this->getClassShortName() . ' already exists', 409);
        }
    }

    public function getClassShortName()
    {
        $path = explode('\\', $this->getClassName());
        return array_pop($path);
    }

    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }
}
