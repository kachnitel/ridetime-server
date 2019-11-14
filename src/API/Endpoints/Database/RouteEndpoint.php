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

    public function listByLocation(int $locationId): array
    {
        $results = $this->entityManager->getRepository(Route::class)->listByLocation($locationId);
        return array_map(function(Route $route) { return $route->getDetail(); }, $results);
    }
}
