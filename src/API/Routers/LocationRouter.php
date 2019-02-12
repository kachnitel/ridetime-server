<?php
namespace RideTimeServer\API\Routers;

use Slim\App;

class LocationRouter implements RouterInterface
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function initRoutes()
    {
        /** List locations */
        $this->app->get('/locations', 'RideTimeServer\API\Controllers\LocationController:list');
        // $this->app->get('/locations/{id}', 'RideTimeServer\API\Controllers\LocationController:get');
        // $this->app->post('/locations', 'RideTimeServer\API\Controllers\LocationController:add');
    }
}
