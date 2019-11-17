<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\API\Endpoints\EntityEndpointInterface;
use RideTimeServer\Entities\Route;
use RideTimeServer\Exception\RTException;

class RouteEndpoint extends ThirdPartyEndpoint implements EntityEndpointInterface
{
    const ENTITY_CLASS = Route::class;

    public function list(?array $ids): array
    {
        throw new RTException('Routes list method not implemented', 501);
        return [];
    }

    /**
     * REVIEW: $detail param inconsistent with rest of API
     * @param integer $locationId
     * @param bool $detail Returns the Entity if false
     * @return object[]
     */
    public function listByLocation(int $locationId, bool $detail = true): array
    {
        $results = $this->entityManager->getRepository(Route::class)->listByLocation($locationId);
        return $detail
            ? array_map(function(Route $route) { return $route->getDetail(); }, $results)
            : $results;
    }
}
