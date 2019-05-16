<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\Location;
use Doctrine\Common\Collections\Criteria;
use RideTimeServer\API\Endpoints\EntityEndpointInterface;

class LocationEndpoint extends BaseEndpoint implements EntityEndpointInterface
{
        /**
     * Get location detail
     *
     * @param Location $location
     * @return object
     */
    public function getDetail(Location $location): object
    {
        return (object) [
            'id' => $location->getId(),
            'name' => $location->getName(),
            'coords' => [
                $location->getGpsLat(),
                $location->getGpsLon()
            ],
            'difficulties' => $location->getDifficulties()
        ];
    }

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

        return $this->listEntities(Location::class, [$this, 'getDetail'], $criteria);
    }

    public function addMultiple(array $items)
    {
        foreach ($items as $item) {
            $location = $this->entityManager->find(Location::class, $item->id) ?? new Location();
            $location->setId($item->id);
            $location->setName($item->name);
            $location->setDifficulties($item->difficulties);
            $location->setGpsLat($item->coords[0]);
            $location->setGpsLon($item->coords[1]);

            $this->entityManager->persist($location);
        }
        $this->entityManager->flush();
    }
}