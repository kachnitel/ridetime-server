<?php
namespace RideTimeServer\API\Repositories;

use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Entities\Location;

class TrailRepository extends BaseTrailforksRepository implements RemoteSourceRepositoryInterface
{
    const API_FIELDS = [
        'trailid',
        'title',
        'difficulty',
        'stats',
        'description',
        'rid',
        'alias'
    ];

    const API_ID_FIELD = 'trailid';

    /**
     * Call API with $filter
     *
     * @param string $filter
     * @return Trail[]
     */
    public function remoteFilter(string $filter): array
    {
        $data = $this->connector->trails($filter, self::API_FIELDS);
        return array_map([$this, 'upsert'], $data);
    }

    /**
     * @param Trail $trail
     * @param object $data
     * @return PrimaryEntity
     */
    protected function populateEntity(PrimaryEntity $trail, object $data): PrimaryEntity
    {
        $data->id = $data->trailid;
        /** @var Trail $trail */
        $trail->applyProperties($data);

        $trail->setProfile($data->stats);

        $location = $this->getEntityManager()
            ->getRepository(Location::class)
            ->findWithFallback($data->rid);
        $trail->setLocation($location);

        return $trail;
    }
}
