<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\API\Endpoints\EntityEndpointInterface;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Exception\RTException;

class TrailEndpoint extends ThirdPartyEndpoint implements EntityEndpointInterface
{
    const ENTITY_CLASS = Trail::class;

    /**
     * @param integer $locationId
     * @return Trail[]
     */
    public function listByLocation(int $locationId): array
    {
        return $this->entityManager
            ->getRepository(self::ENTITY_CLASS)
            ->listByLocation($locationId);
    }
}
