<?php
namespace RideTimeServer\API\Endpoints\Database;

use RideTimeServer\Entities\Location;
use Doctrine\Common\Collections\Criteria;

class LocationEndpoint extends ThirdPartyEndpoint implements ThirdPartyEndpointInterface
{
    const ENTITY_CLASS = Location::class;

    /**
     * @return array[Location]
     */
    public function list(?array $ids): array
    {
        $criteria = Criteria::create()
            // ->orderBy(array('date' => Criteria::ASC)) // distance by default
            ->setMaxResults(20);

        if ($ids) {
            $criteria->where(Criteria::expr()->in('id', $ids));
        }

        return $this->listEntities(static::ENTITY_CLASS, $criteria);
    }
}
