<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\Location;
use Doctrine\Common\Collections\Criteria;

class LocationEndpoint extends ThirdPartyEndpoint implements ThirdPartyEndpointInterface
{
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

    protected function upsert(object $data): Location
    {
        $location = $this->entityManager->find(Location::class, $data->id) ?? new Location();
        $location->setId($data->id);
        $location->setName($data->name);
        $location->setDifficulties($data->difficulties);
        $location->setGpsLat($data->coords[0]);
        $location->setGpsLon($data->coords[1]);

        $this->entityManager->persist($location);

        return $location;
    }
}