<?php
namespace RideTimeServer\API\Endpoints;

use Doctrine\ORM\EntityManager;
use Monolog\Logger;

use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\EntityInterface;

class LocationEndpoint extends Endpoint implements EndpointInterface
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

    // /**
    //  * @param integer $locationId
    //  * @return Location
    //  */
    // public function get(int $locationId): Location
    // {
    //     return $this->getEntity(Location::class, $locationId);
    // }

    /**
     * @return array[Location]
     */
    public function list(): array
    {
        return $this->listEntities(Location::class, [$this, 'getDetail']);
    }
}