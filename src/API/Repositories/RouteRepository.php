<?php
namespace RideTimeServer\API\Repositories;

use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\Route;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Exception\EntityNotFoundException;

class RouteRepository extends BaseTrailforksRepository implements RemoteSourceRepositoryInterface
{
    const API_FIELDS = [
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
        'alias',
        'trails',
        'stats'
    ];

    const API_ID_FIELD = 'id';

    public function listByLocation(int $locationId): array
    {
        return $this->remoteFilter('activitytype::1;rid::' . $locationId);
    }

    /**
     * Call API with $filter
     *
     * @param string $filter
     * @return Route[]
     */
    public function remoteFilter(string $filter): array
    {
        $data = $this->connector->routes($filter, self::API_FIELDS);
        return array_map([$this, 'upsert'], $data);
    }

    /**
     * @param Route $route
     * @param object $data
     * @return PrimaryEntity
     */
    protected function populateEntity(PrimaryEntity $route, object $data): PrimaryEntity
    {
        /** @var Route $route */
        $route->applyProperties($data);

        $location = $this->getEntityManager()
            ->getRepository(Location::class)
            ->findWithFallback($data->rid);
        $route->setLocation($location);

        $route->setProfile($data->stats);

        if ($data->trails) {
            foreach ($data->trails as $trailInfo) {
                try {
                    $trail = $this->getEntityManager()
                        ->getRepository(Trail::class)
                        ->findWithFallback($trailInfo->trailid);
                } catch (EntityNotFoundException $e) {
                    /**
                     * TODO: https://github.com/kachnitel/ridetime-server/issues/28
                     */
                    continue;
                }
                $route->addTrail($trail);
            }
        }

        return $route;
    }
}
