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

    public function listByLocation(int $locationId): array
    {
        return $this->remoteFilter('rid::' . $locationId);
    }

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
     * Prepare scalar values for Trail::applyProperties
     *
     * @param object $trailData
     * @return object
     */
    protected function transform(object $trailData): object
    {
        return (object) [
            'id' => $trailData->trailid,
            'title' => $trailData->title,
            'description' => $trailData->description,
            'difficulty' => $trailData->difficulty - 3, // TF uses different diff. ratings
            'alias' => $trailData->alias
        ];
    }

    /**
     * @param Trail $trail
     * @param object $data
     * @return PrimaryEntity
     */
    protected function populateEntity(PrimaryEntity $trail, object $data): PrimaryEntity
    {
        $scalarData = $this->transform($data);

        /** @var Trail $trail */
        $trail->applyProperties($scalarData);

        $trail->setProfile($data->stats);

        $location = $this->getEntityManager()
            ->getRepository(Location::class)
            ->findWithFallback($data->rid);
        $trail->setLocation($location);

        return $trail;
    }
}
