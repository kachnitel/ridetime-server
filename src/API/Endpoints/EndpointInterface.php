<?php
namespace RideTimeServer\API\Endpoints;

use Monolog\Logger;
use RideTimeServer\Entities\EntityInterface;

interface EndpointInterface
{
    /**
     * Add an entity
     *
     * @param array $data
     * @param Logger $logger
     * @return array Returns detail of the created entity
     */
    public function add(array $data, Logger $logger): object;

    /**
     * Get entity from Doctrine
     *
     * @param integer $id
     * @return EntityInterface
     */
    public function get(int $id): EntityInterface;

    /**
     * Get entity detail
     *
     * @param EntityInterface $entity
     * @return array
     */
    public function getDetail(EntityInterface $entity): object;

}
