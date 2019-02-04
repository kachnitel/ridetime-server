<?php
namespace RideTimeServer\API\Routers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use RideTimeServer\API\Endpoints\UserEndpoint;

use Slim\App;

class UserRouter implements RouterInterface
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
     *
     * @return void
     */
    public function initRoutes()
    {
        $this->app->post('/users', 'RideTimeServer\API\Controllers\UserController:add');
        $this->app->get('/users/{id}', 'RideTimeServer\API\Controllers\UserController:get');
    }
}