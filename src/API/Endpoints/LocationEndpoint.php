<?php
namespace RideTimeServer\API\Endpoints;

use RideTimeServer\Entities\Location;
use Doctrine\Common\Collections\Criteria;

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
}