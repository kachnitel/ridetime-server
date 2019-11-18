<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\Location;
use RideTimeServer\API\Repositories\LocationRepository;
use Doctrine\Common\Collections\Criteria;

class LocationEndpoint extends ThirdPartyEndpoint implements ThirdPartyEndpointInterface
{
    const ENTITY_CLASS = Location::class;

    /**
     * @return Location[]
     */
    public function list(?array $ids = null): array
    {
        $criteria = Criteria::create()
            // ->orderBy(array('date' => Criteria::ASC)) // distance by default
            ->setMaxResults(20);

        if ($ids) {
            $criteria->where(Criteria::expr()->in('id', $ids));
        }

        return $this->listEntities(static::ENTITY_CLASS, $criteria);
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

        return $this->filter($filter);
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

        return $this->filter($filter);
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

        return $this->filter($filter);
    }

    /**
     * @param string $filter
     * @return Location[]
     */
    protected function filter(string $filter): array
    {
        /** @var LocationRepository $repo */
        $repo = $this->entityManager->getRepository(self::ENTITY_CLASS);
        return $repo->remoteFilter($filter);
    }
}
