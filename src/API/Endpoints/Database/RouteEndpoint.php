<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\API\Endpoints\EntityEndpointInterface;
use RideTimeServer\Entities\Route;
use RideTimeServer\Exception\RTException;

class RouteEndpoint extends ThirdPartyEndpoint implements EntityEndpointInterface
{
    const ENTITY_CLASS = Route::class;

    public function list(?array $ids = null): array
    {
        throw new RTException('Routes list method not implemented', 501);
        return [];
    }

    /**
     * @param integer $locationId
     * @return Route[]
     */
    public function listByLocation(int $locationId): array
    {
        return $this->entityManager
            ->getRepository(self::ENTITY_CLASS)
            ->listByLocation($locationId);
    }
}
