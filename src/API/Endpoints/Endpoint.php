<?php
namespace RideTimeServer\API\Endpoints;

use Doctrine\ORM\EntityManager;

class Endpoint
{
    /**
     * Doctrine entity manager
     *
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }
}
