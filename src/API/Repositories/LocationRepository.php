<?php
namespace RideTimeServer\API\Repositories;

use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\PrimaryEntity;

class LocationRepository extends BaseTrailforksRepository implements RemoteSourceRepositoryInterface
{
    const API_FIELDS = [
        'rid',
        'title',
        'alias',
        'hidden',
        'changed',
        'latitude',
        'longitude',
        'search',
        'imagemap', // https://ep1.pinkbike.org/files/regionmaps/{imagemap}
        'shuttle',
        'bikepark',
        // Difficulties
        'tc_3', // green
        'tc_4', // blue
        'tc_5', // black
        'tc_6'  // double black
    ];

    const API_ID_FIELD = 'rid';

    const REGION_MAP_URL_PREFIX = 'https://ep1.pinkbike.org/files/regionmaps/';

    /**
     * Call API with $filter
     *
     * @param string $filter
     * @return Location[]
     */
    public function remoteFilter(string $filter): array
    {
        $data = $this->connector->locations($filter . ';bottom::ridingarea', self::API_FIELDS);
        return array_map([$this, 'upsert'], $data);
    }

    /**
     * Filter locations within $range from $latLon
     *
     * @param array $latLon [lat, lon]
     * @param integer $range In km
     * @return Location[]
     */
    public function nearby(array $latLon, int $range): array
    {
        $filter = "nearby_range::{$range};lat::{$latLon[0]};lon::{$latLon[1]}";

        return $this->remoteFilter($filter);
    }

    /**
     * Bounding box filtered locations
     *
     * bbox filter is in the format of
     * top-left lat/lon and bottom-right lat/lon
     * values seperated by commas.
     *
     * Example: bbox::49.33,-122.973,49.322,-122.957
     *
     * @param float[] $bbox
     * @return Location[]
     */
    public function bbox(array $bbox): array
    {
        $boundary = join(',', $bbox);
        $filter = "bbox::{$boundary}";

        return $this->remoteFilter($filter);
    }

    /**
     * Search by name
     *
     * @param string $name
     * @return Location[]
     */
    public function search(string $name): array
    {
        $filter = "search::{$name}";

        return $this->remoteFilter($filter);
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
        // TODO:
        // 'imagemap' => $location->imagemap ? self::REGION_MAP_URL_PREFIX . $location->imagemap : null,
        // 'shuttle' => (bool) $location->shuttle,
        // 'bikepark' => (bool) $location->bikepark

        $location->setId($data->rid);
        $location->setName($data->title);
        $location->setGpsLat($data->latitude);
        $location->setGpsLon($data->longitude);
        $location->setDifficulties((object) [
            3 => (int) $data->tc_3,
            4 => (int) $data->tc_4,
            5 => (int) $data->tc_5,
            6 => (int) $data->tc_6
        ]);
        $location->setAlias($data->alias);

        return $location;
    }
}
