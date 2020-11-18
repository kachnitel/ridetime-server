<?php
namespace RideTimeServer\API\Repositories;

use RideTimeServer\Entities\PrimaryEntity;

interface RemoteSourceRepositoryInterface
{
    public function findRemote(int $id): PrimaryEntity;

    /**
     * TODO: Should be possible to use in Base class
     * Fetch items from remote API using $filter
     *
     * @param string $filter
     * @return array
     */
    public function remoteFilter(string $filter): array;
}
