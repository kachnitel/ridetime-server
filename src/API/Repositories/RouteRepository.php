<?php
namespace RideTimeServer\API\Repositories;

use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\Route;
use RideTimeServer\Entities\Trail;

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
        'trails',
        'stats'
    ];

    const API_ID_FIELD = 'id';

    public function listByLocation(int $locationId): array
    {
        $results = $this->connector->getLocationRoutes($locationId, static::API_FIELDS);

        return array_map([$this, 'upsert'], $results);
    }

    protected function transform(object $data): object
    {
        $trails = array_map(function($trail) { return $trail->trailid; }, $data->trails);

        return (object) [
            'id' => $data->id,
            'title' => $data->title,
            'description' => $data->description,
            'difficulty' => $data->difficulty - 3, // TF uses different diff. ratings
            'profile' => $data->stats,
            'location' => $data->rid,
            'trails' => $trails
        ];
    }

    /**
     * @param Route $entity
     * @param object $data
     * @return PrimaryEntity
     */
    protected function populateEntity(PrimaryEntity $entity, object $data): PrimaryEntity
    {
        /** @var Route $entity */
        $entity->applyProperties($data);

        $location = $this->getEntityManager()
            ->getRepository(Location::class)
            ->findWithFallback($data->location);
        $entity->setLocation($location);

        $entity->setProfile($data->profile);

        foreach ($data->trails as $trailId) {
            $trail = $this->getEntityManager()
                ->getRepository(Trail::class)
                ->findWithFallback($trailId);
            $entity->addTrail($trail);
        }

        return $entity;
    }
}
