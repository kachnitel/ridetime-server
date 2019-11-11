<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\API\Endpoints\EntityEndpointInterface;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Exception\RTException;

class TrailEndpoint extends ThirdPartyEndpoint implements EntityEndpointInterface
{
    const ENTITY_CLASS = Trail::class;

    /**
     * @param integer $trailId
     * @return Trail
     */
    public function get(int $trailId): Trail
    {
        return $this->getEntity(static::ENTITY_CLASS, $trailId);
    }

    public function list(?array $ids): array
    {
        throw new RTException('Trails list method not implemented', 501);
        return [];
    }

    public function listByLocation(int $locationId): array
    {
        $results = $this->TfEndpoint->getLocationTrails($locationId);
        return $this->addMultiple($results);
    }

    /**
     * @param Trail $trail
     * @param object $data
     * @return PrimaryEntity
     */
    protected function populateEntity($trail, object $data): PrimaryEntity
    {
        $trail->applyProperties($data);
        $trail->setProfile($data->profile);

        $location = $this->getEntity(Location::class, $data->location);
        $trail->setLocation($location);

        return $trail;
    }
}
