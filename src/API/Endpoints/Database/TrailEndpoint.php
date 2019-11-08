<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\API\Endpoints\EntityEndpointInterface;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Exception\RTException;

class TrailEndpoint extends ThirdPartyEndpoint implements EntityEndpointInterface
{
    const ENTITY_CLASS = Trail::class;

    /**
     * @param integer $trailId
     * @return Trail
     */
    public function get(int $trailId): Trail
    {
        return $this->getEntity(Trail::class, $trailId);
    }

    public function list(?array $ids): array
    {
        throw new RTException('Trails list method not implemented', 501);
        return [];
    }

    /**
     * Find a Trail by $attribute
     * TODO: UserEndpoint duplicate exc. findBy vs findOneBy
     * REVIEW: Unused at this point - remove / move to parent? Could be used for non-api filters
     *
     * @param string $attribute
     * @param string $value
     * @return Trail[]
     */
    public function findBy(string $attribute, string $value): array
    {
        try {
            $result = $this->entityManager->getRepository(Trail::class)->findBy([$attribute => $value]);
        } catch (\Doctrine\ORM\ORMException $e) {
            throw new RTException("Error looking up Trail by {$attribute} = {$value}", 0, $e);
        }
        if (empty($result)) {
            throw new EntityNotFoundException("Trail with {$attribute} = {$value} not found", 404);
        }
        return $result;
    }

    /**
     * @param Trail $trail
     * @param object $data
     * @return PrimaryEntity
     */
    protected function populateEntity($trail, object $data): PrimaryEntity
    {
        $trail->applyProperties($data);
        $trail->setProfile($data->profile);

        $location = $this->getEntity(Location::class, $data->location);
        $trail->setLocation($location);

        return $trail;
    }
}
