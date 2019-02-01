<?php
namespace RideTimeServer\API;

use Slim\App;
use RideTimeServer\API\Routers\UserRouter;

class RouterLoader implements RouterLoaderInterface
{
    /**
     * Slim app
     *
     * @var App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function initRoutes()
    {
        $routers = [
            new UserRouter($this->app)
        ];

        foreach ($routers as $router) {
            $router->initRoutes();
        }
    }
}