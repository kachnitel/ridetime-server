<?php
namespace RideTimeServer\Entities;

class PrimaryEntity
{
    /**
     * Undocumented function
     *
     * @param PrimaryEntityInterface[] $entities
     * @return int[]
     */
    protected function extractIds(array $entities): array
    {
        return array_map(
            function(PrimaryEntityInterface $item) {return $item->getId();},
            $entities
        );
    }

    protected function extractDetails(array $entities): array
    {
        return array_map(
            function(PrimaryEntityInterface $item) {return $item->getDetail();},
            $entities
        );
    }
}
