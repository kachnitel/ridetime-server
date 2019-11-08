<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\Location;
use Doctrine\Common\Collections\Criteria;
use RideTimeServer\Entities\PrimaryEntity;

class LocationEndpoint extends ThirdPartyEndpoint implements ThirdPartyEndpointInterface
{
    const ENTITY_CLASS = Location::class;

    /**
     * @param integer $locationId
     * @return Location
     */
    public function get(int $locationId): Location
    {
        return $this->getEntity(Location::class, $locationId);
    }

    /**
     * @return array[Location]
     */
    public function list(?array $ids): array
    {
        $criteria = Criteria::create()
            // ->orderBy(array('date' => Criteria::ASC)) // distance by default
            ->setMaxResults(20);

        if ($ids) {
            $criteria->where(Criteria::expr()->in('id', $ids));
        }

        return $this->listEntities(Location::class, $criteria);
    }

    /**
     * Fill existing entity with proper formed data
     *
     * @param Location $location
     * @param object $data
     * @return Location
     */
    protected function populateEntity($location, object $data): PrimaryEntity
    {
        $location->setId($data->id);
        $location->setName($data->name);
        $location->setDifficulties($data->difficulties);
        $location->setGpsLat($data->coords[0]);
        $location->setGpsLon($data->coords[1]);

        return $location;
    }
}
