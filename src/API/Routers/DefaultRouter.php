<?php

namespace RideTimeServer\API\Routers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Slim\App;
use RideTimeServer\API\Controllers\DefaultController;

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
        $controllerSupportedRoutes = array_keys(DefaultController::SUPPORTED_ENTITY_ENDPOINTS);
        $routeMatch = '/{entityType:' . implode('|', $controllerSupportedRoutes) . '}';

        $this->app->post($routeMatch, 'RideTimeServer\API\Controllers\DefaultController:add');
        $this->app->get($routeMatch . '/{id}', 'RideTimeServer\API\Controllers\DefaultController:get');
    }
}
