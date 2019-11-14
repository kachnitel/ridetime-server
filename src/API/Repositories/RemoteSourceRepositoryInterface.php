<?php
namespace RideTimeServer\API\Repositories;

use RideTimeServer\Entities\PrimaryEntity;

interface RemoteSourceRepositoryInterface
{
    public function findWithFallback(int $id): PrimaryEntity;
}
