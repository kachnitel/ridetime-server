<?php
namespace RideTimeServer\API\Controllers;

use RideTimeServer\API\Endpoints\LocationEndpoint;
use RideTimeServer\API\Endpoints\EndpointInterface;

class LocationController extends BaseController
{
    protected function getEndpoint(): EndpointInterface
    {
        return new LocationEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
