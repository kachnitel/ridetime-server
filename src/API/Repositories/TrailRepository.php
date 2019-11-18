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
        'rid'
    ];

    const API_ID_FIELD = 'trailid';

    public function listByLocation(int $locationId): array
    {
        $results = $this->connector->getLocationTrails($locationId, static::API_FIELDS);

        return array_map([$this, 'upsert'], $results);
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
            'difficulty' => $trailData->difficulty - 3 // TF uses different diff. ratings
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
