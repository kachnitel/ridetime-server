<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\Location;
use Doctrine\Common\Collections\Criteria;

class LocationEndpoint extends BaseEndpoint implements ThirdPartyEndpointInterface
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

    /**
     * @param array $items
     * @return object[]
     */
    public function addMultiple(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $location = $this->upsertLocation($item);
            $result[] = $location->getDetail();
        }
        $this->entityManager->flush();

        return $result;
    }

    protected function upsertLocation(object $data): Location
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