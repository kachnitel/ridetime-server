<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\API\Endpoints\EntityEndpointInterface;

/**
 * Interface to add locally storing entities from a third party
 */
interface ThirdPartyEndpointInterface extends EntityEndpointInterface
{
    /**
     * Process an array of entity data
     * - store in DB
     * - return RT Entity based details
     *
     * @param string $class
     * @param array $items
     * @return array
     */
    public function addMultiple(array $items): array;
}
