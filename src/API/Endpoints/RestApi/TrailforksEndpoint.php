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

    protected function processLocations(array $results): array
    {
        $filtered = array_values($this->filterHidden($results));
        $formatted = array_map([$this, 'getLocationDetail'], $filtered);

        return $formatted;
    }

    protected function filterHidden(array $results): array
    {
        return array_filter($results, function($item) {
            return empty($item->hidden);
        });
    }

    protected function getLocationDetail(object $location): object
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
            'imagemap' => $location->imagemap,
            'shuttle' => (bool) $location->shuttle,
            'bikepark' => (bool) $location->bikepark
        ];
    }
}
