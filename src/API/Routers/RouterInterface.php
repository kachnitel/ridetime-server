<?php
namespace RideTimeServer\API\Routers;

use Slim\App;

interface RouterInterface
{
    public function __construct(App $app);

    public function initRoutes();
}