<?php

namespace RideTimeServer\API\Routers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Slim\App;

class DefaultRouter implements RouterInterface
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

    /**
     * Initialize user routes
     * entityType:users|events
     *
     * @return void
     */
    public function initRoutes()
    {
        $this->app->post('/{entityType:users|events}', 'RideTimeServer\API\Controllers\DefaultController:add');
        $this->app->get('/{entityType:users|events}/{id}', 'RideTimeServer\API\Controllers\DefaultController:get');
    }
}
