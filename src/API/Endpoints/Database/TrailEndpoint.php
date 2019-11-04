<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\API\Endpoints\EntityEndpointInterface;
use RideTimeServer\Entities\Location;
use RideTimeServer\Entities\Trail;
use RideTimeServer\Exception\RTException;

class TrailEndpoint extends BaseEndpoint implements EntityEndpointInterface
{
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
     * REVIEW: Common parent for 3rd party sourced Entities
     *
     * @param array $items
     * @return void
     */
    public function addMultiple(array $items)
    {
        foreach ($items as $item) {
            $this->upsertTrail($item);
        }
        $this->entityManager->flush();
    }

    protected function upsertTrail(object $data): Trail
    {
        $trail = $this->entityManager->find(Trail::class, $data->id) ?? new Trail();
        $trail->applyProperties($data);
        $trail->setLocation($this->getLocation($data->location));
        $trail->setProfile($data->profile);

        $this->entityManager->persist($trail);

        return $trail;
    }

    protected function getLocation(int $id) : Location
    {
        return (new LocationEndpoint($this->entityManager, $this->logger))->get($id);
    }
}