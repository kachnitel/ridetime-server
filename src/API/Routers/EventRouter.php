<?php
namespace RideTimeServer\API\Routers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use RideTimeServer\API\Endpoints\EventEndpoint;

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
        /** Create event */
        $this->app->post('/events', 'RideTimeServer\API\Controllers\EventController:add');

        /** Get event */
        $this->app->get('/events/{id}', 'RideTimeServer\API\Controllers\EventController:get');

        /** Add event member */
        $this->app->post('/events/{id}/members', 'RideTimeServer\API\Controllers\EventController:addMember');
    }
}
