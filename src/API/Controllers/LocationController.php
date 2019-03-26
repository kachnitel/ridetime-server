<?php
namespace RideTimeServer\API\Controllers;

use RideTimeServer\API\Endpoints\LocationEndpoint;

class LocationController extends BaseController
{
    /**
     * @return LocationEndpoint
     */
    protected function getEndpoint()
    {
        return new LocationEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
