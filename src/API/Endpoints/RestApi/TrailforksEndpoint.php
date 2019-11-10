<?php
namespace RideTimeServer\API\Endpoints\RestApi;

use RideTimeServer\API\Connectors\TrailforksConnector;

/**
 * REVIEW:
 * Could move locationsNearby and BBox to LocationEndpoint
 * deprecating this class
 * since all API logic is in Connector
 */
class TrailforksEndpoint
{
    const REGION_MAP_URL_PREFIX = 'https://ep1.pinkbike.org/files/regionmaps/';

    /**
     * @var TrailforksConnector
     */
    protected $connector;
    protected $fields = [
        'locations' => [
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
        ],
        'trail' => [
            'trailid',
            'title',
            'difficulty',
            'stats',
            'description',
            'rid'
        ]
    ];

    public function __construct(TrailforksConnector $connector)
    {
        $this->connector = $connector;
    }

    public function locationsBBox(array $bbox): array
    {
        return $this->processLocations(
            $this->connector->getLocationsBBox($bbox, $this->fields['locations'])
        );
    }

    public function locationsNearby(array $latLon, int $range): array
    {
        return $this->processLocations(
            $this->connector->getLocationsNearby($latLon, $range, $this->fields['locations'])
        );
    }

    public function locationsSearch(string $search): array
    {
        return $this->processLocations(
            $this->connector->searchLocations($search, $this->fields['locations'])
        );
    }

    protected function processLocations(array $results): array
    {
        $filtered = array_values($this->filterHidden($results));
        $formatted = array_map([$this, 'getLocationData'], $filtered);

        return $formatted;
    }

    protected function filterHidden(array $results): array
    {
        return array_filter($results, function($item) {
            return empty($item->hidden);
        });
    }

    /**
     * Convert Trailforks Location API values into RT format
     *
     * @param object $location
     * @return object
     */
    protected function getLocationData(object $location): object
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
     * Get a single trail by ID
     *
     * @param integer $id
     * @return object
     */
    public function getTrail(int $id): object
    {
        $result = $this->connector->getTrail($id, $this->fields['trail']);
        return $this->transformTrail($result);
    }

    /**
     * Convert Trailforks trail information to RT format
     *
     * @param integer $locationId
     * @return array
     */
    public function getLocationTrails(int $locationId): array
    {
        $results = $this->connector->getLocationTrails($locationId, $this->fields['trail']);

        return array_map([$this, 'transformTrail'], $results);
    }

    protected function transformTrail(object $trailData): object
    {
        return (object) [
            'id' => $trailData->trailid,
            'title' => $trailData->title,
            'description' => $trailData->description,
            'difficulty' => $trailData->difficulty - 3, // TF uses different diff. ratings
            'profile' => $trailData->stats,
            'location' => $trailData->rid
        ];
    }

    public function getLocationRoutes(int $locationId): array
    {
        $results = $this->connector->getLocationRoutes($locationId, [
            'id',
            'rid',
            'title',
            'difficulty',
            // 'biketype',
            'description',
            // 'cover_photo',
            // 'prov_abv',
            // 'city_title',
            // 'country_title',
            'trails',
            'stats'
        ]);

        return array_map(function(object $item) {
            return (object) [
                'id' => $item->id,
                'title' => $item->title,
                'description' => $item->description,
                'difficulty' => $item->difficulty - 3, // TF uses different diff. ratings
                'profile' => $item->stats,
                'location' => $item->rid,
                'trails' => $item->trails
            ];
        }, $results);
    }
}
