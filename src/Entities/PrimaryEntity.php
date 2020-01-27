<?php
namespace RideTimeServer\Entities;

abstract class PrimaryEntity implements PrimaryEntityInterface
{
    /**
     * Extract values from related entities
     *
     * @param PrimaryEntityInterface[] $entities
     * @return int[]
     */
    protected function extractIds(array $entities): array
    {
        return array_map(
            function (PrimaryEntityInterface $item) {return $item->getId();},
            $entities
        );
    }

    protected function extractDetails(array $entities): array
    {
        return array_map(
            function (PrimaryEntityInterface $item) {
                return $item->getDetail();
            },
            $entities
        );
    }

    public function __toString()
    {
        return (string) $this->getId();
    }
}
