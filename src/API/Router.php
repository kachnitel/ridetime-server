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
            /** Get event detail */
            $app->get('/events/{id}', 'RideTimeServer\API\Controllers\EventController:get');
            /** Create event */
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
            /** Get user detail */
            $app->get('/users/{id}', 'RideTimeServer\API\Controllers\UserController:get');
            /** Update user */
            $app->put('/users/{id}', 'RideTimeServer\API\Controllers\UserController:update');
            /** Update user's picture */
            $app->post('/users/{id}/picture', 'RideTimeServer\API\Controllers\UserController:uploadPicture');
            /** Request friendship */
            $app->post('/users/{id}/friends/{friendId}', 'RideTimeServer\API\Controllers\UserController:addFriend');
            /** Accept friendship */
            $app->put(
                '/users/{id}/friends/{friendId}/accept',
                'RideTimeServer\API\Controllers\UserController:acceptFriend'
            );
        });
    }
}
