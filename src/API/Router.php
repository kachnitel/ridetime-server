<?php
namespace RideTimeServer\API;

use Slim\App;

class Router
{
    /**
     * @var App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * @return void
     */
    public function initRoutes()
    {
        /** Return user detail */
        $this->app->post('/signin', 'RideTimeServer\API\Controllers\AuthController:signIn');
        $this->app->post('/signup', 'RideTimeServer\API\Controllers\AuthController:signUp');

        $app = $this->app;
        $this->app->group('/api', function () use ($app) {
            /**
             * Events
             */
            /** List events */
            $app->get('/events', 'RideTimeServer\API\Controllers\EventController:list');
            $app->get('/events/{id}', 'RideTimeServer\API\Controllers\EventController:get');
            $app->post('/events', 'RideTimeServer\API\Controllers\EventController:add');
            /** Add event member */
            $app->post('/events/{id}/members', 'RideTimeServer\API\Controllers\EventController:addMember');

            /**
             * Locations
             */
            /** List locations */
            $app->get('/locations', 'RideTimeServer\API\Controllers\LocationController:list');

            /**
             * Users
             */
            $app->get('/users/{id}', 'RideTimeServer\API\Controllers\UserController:get');
            $app->post('/users', 'RideTimeServer\API\Controllers\UserController:add');
            $app->put('/users/{id}', 'RideTimeServer\API\Controllers\UserController:update');
        });
    }
}
