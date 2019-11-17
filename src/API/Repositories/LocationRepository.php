<?php
namespace RideTimeServer\API\Repositories;

use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\PrimaryEntity;

class LocationRepository extends BaseTrailforksRepository implements RemoteSourceRepositoryInterface
{
    const API_FIELDS = [
        "rid",
        "title",
        "alias",
        "hidden",
        "changed",
        "latitude",
        "longitude",
        "search",
        "imagemap", // https://ep1.pinkbike.org/files/regionmaps/{imagemap}
        "shuttle",
        "bikepark",
        // Difficulties
        "tc_3", // green
        "tc_4", // blue
        "tc_5", // black
        "tc_6"  // double black
    ];

    const API_ID_FIELD = 'rid';

    const REGION_MAP_URL_PREFIX = 'https://ep1.pinkbike.org/files/regionmaps/';

    /**
     * Call API with $filter
     *
     * @param string $filter
     * @return void
     */
    public function remoteFilter(string $filter): array
    {
        $data = $this->connector->locations($filter, self::API_FIELDS);
        $results = array_map([$this, 'upsert'], $data);
        return array_map(function(Location $location) { return $location->getDetail(); }, $results);
    }

    /**
     * Convert Trailforks Location API values into RT format
     *
     * @param object $location
     * @return object
     */
    protected function transform(object $location): object
    {
        return (object) [
            'id' => (int) $location->rid,
            'name' => $location->title,
            'coords' => [
                (float) $location->latitude,
                (float) $location->longitude
            ],
            'difficulties' => (object) [
                0 => (int) $location->tc_3,
                1 => (int) $location->tc_4,
                2 => (int) $location->tc_5,
                3 => (int) $location->tc_6
            ],
            'imagemap' => $location->imagemap ? self::REGION_MAP_URL_PREFIX . $location->imagemap : null,
            'shuttle' => (bool) $location->shuttle,
            'bikepark' => (bool) $location->bikepark
        ];
    }

    /**
     * Fill existing entity with proper formed data
     *
     * @param Location $location
     * @param object $data
     * @return Location
     */
    protected function populateEntity(PrimaryEntity $location, object $data): PrimaryEntity
    {
        $data = $this->transform($data);

        $location->setId($data->id);
        $location->setName($data->name);
        $location->setDifficulties($data->difficulties);
        $location->setGpsLat($data->coords[0]);
        $location->setGpsLon($data->coords[1]);

        return $location;
    }
}
