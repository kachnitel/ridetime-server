<?php
namespace RideTimeServer\API\Endpoints;

use RideTimeServer\Entities\EntityInterface;

interface EntityEndpointInterface extends EndpointInterface
{
    /**
     * Retrieve an entity by ID
     *
     * @param integer $id
     * @return EntityInterface
     */
    public function get (int $id);

    /**
     * @return array[EntityInterface]
     */
    public function list(?array $ids = null): array;
}
