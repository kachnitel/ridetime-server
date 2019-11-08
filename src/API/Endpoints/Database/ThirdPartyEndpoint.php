<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\PrimaryEntity;
use RideTimeServer\Entities\PrimaryEntityInterface;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\RTException;

abstract class ThirdPartyEndpoint extends BaseEndpoint
{

    /**
     * TODO: ...before creating RouteEndpoint that uses the same
     * REVIEW: upsert can be deduped as well, only leaving the actual property setting in final class
     *
     * @param array $items
     * @return object[]
     */
    public function addMultiple(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $entity = $this->upsert($item);
            $result[] = $entity->getDetail();
        }
        $this->entityManager->flush();

        return $result;
    }

    /**
     * Create new item or update existing with new data
     *
     * @param object $item
     * @return PrimaryEntity
     */
    abstract protected function upsert(object $item);

    /**
     * TODO: this method could be available in 3p, data coming from app/user
     *  should also be objects like from APIs
     *
     * @param array $data
     * @param User $currentUser
     * @return PrimaryEntityInterface
     */
    protected function create(array $data, User $currentUser): PrimaryEntity
    {
        throw new RTException("Manually creating 3p endpoints not implemented", 501);
        return $this->upsert((object) $data);
    }
}
