<?php
namespace RideTimeServer\API;

use Slim\App;
use RideTimeServer\API\Middleware\CurrentUserMiddleware;

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
        $cuMiddleware = new CurrentUserMiddleware($app->getContainer());

        $that = $this; // bit JavaScripty
        $this->app->group('/api', function (App $app) use ($that) {
            $that->initEventRoutes($app);
            $that->initLocationRoutes($app);
            $that->initUserRoutes($app);
        })->add($cuMiddleware->getMiddleware());

        $this->app->group('/dashboard', function (App $app) use ($that) {
            $that->initDashboardRoutes($app);
        })->add($cuMiddleware->getMiddleware(true));
    }

    protected function initEventRoutes(App $app)
    {
        /** List events */
        $app->get('/events', 'RideTimeServer\API\Controllers\EventController:list');
        /** Get event detail */
        $app->get('/events/{id}', 'RideTimeServer\API\Controllers\EventController:get');
        /** Create event */
        $app->post('/events', 'RideTimeServer\API\Controllers\EventController:add');
        /** Add event member */
        $app->post('/events/{id}/members', 'RideTimeServer\API\Controllers\EventController:addMember');
    }

    protected function initLocationRoutes(App $app)
    {
        /** List locations */
        $app->get('/locations', 'RideTimeServer\API\Controllers\LocationController:list');
    }

    protected function initUserRoutes(App $app)
    {
        /** List users */
        $app->get('/users', 'RideTimeServer\API\Controllers\UserController:list');
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
        $app->delete('/users/{id}/friends/{friendId}', 'RideTimeServer\API\Controllers\UserController:removeFriend');
    }

    protected function initDashboardRoutes(App $app)
    {
        $app->get('', 'RideTimeServer\API\Controllers\DashboardController:all');
    }
}
