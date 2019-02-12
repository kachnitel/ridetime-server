<?php
namespace RideTimeServer\API\Routers;

use Slim\App;

class EventRouter implements RouterInterface
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
        /** Add event member */
        $this->app->post('/events/{id}/members', 'RideTimeServer\API\Controllers\EventController:addMember');
        /** List events */
        $this->app->get('/events', 'RideTimeServer\API\Controllers\EventController:list');
        $this->app->get('/events/{id}', 'RideTimeServer\API\Controllers\EventController:get');
        $this->app->post('/events', 'RideTimeServer\API\Controllers\EventController:add');
    }
}
