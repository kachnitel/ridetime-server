<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\API\Endpoints\EntityEndpointInterface;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Exception\RTException;

class TrailEndpoint extends ThirdPartyEndpoint implements EntityEndpointInterface
{
    const ENTITY_CLASS = Trail::class;

    public function list(?array $ids = null): array
    {
        throw new RTException('Trails list method not implemented', 501);
        return [];
    }

    public function listByLocation(int $locationId): array
    {
        $results = $this->entityManager->getRepository(Trail::class)->listByLocation($locationId);
        return array_map(function(Trail $trail) { return $trail->getDetail(); }, $results);
    }
}
