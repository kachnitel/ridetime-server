<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\API\Endpoints\EntityEndpointInterface;
use RideTimeServer\Entities\PrimaryEntity;

/**
 * Interface to add locally storing entities from a third party
 */
interface ThirdPartyEndpointInterface extends EntityEndpointInterface
{
    // TODO: list?

    public function get(int $entityId): PrimaryEntity;
}
