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

        $this->app->group('/notifications', function (App $app) use ($that) {
            $that->initNotificationsRoutes($app);
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
        /**
         * POST /events/{id}/join (Request join) // check for status invited => accept; public ? confirmed : requested
         * PUT /events/{id}/join/{userId} (Accept request from userId)
         * DEL /events/{id}/join/{userId} (Decline request)
         * POST /events/{id}/invite [ids] // accept existing status == requested
         * PUT /events/{id}/invite (Accept)
         * DEL /events/{id}/invite (Decline)
         */
    }

    protected function initLocationRoutes(App $app)
    {
        /** List locations */
        $app->get('/locations', 'RideTimeServer\API\Controllers\LocationController:list');
        /** Nearby locations */
        $app->get('/locations/nearby', 'RideTimeServer\API\Controllers\LocationController:nearby');
        /** Bounding box */
        $app->get('/locations/bbox', 'RideTimeServer\API\Controllers\LocationController:bbox');
        /** Search */
        $app->get('/locations/search', 'RideTimeServer\API\Controllers\LocationController:search');
        /** TODO: List location top routes and trails */
        // $app->get('/locations/{id}/popular', 'RideTimeServer\API\Controllers\LocationController:popular');
        /** TODO: Search location trails and routes */
    }

    protected function initUserRoutes(App $app)
    {
        /** List users */
        $app->get('/users', 'RideTimeServer\API\Controllers\UserController:list');
        /** Search users */
        $app->get('/users/search', 'RideTimeServer\API\Controllers\UserController:search');
        /** Get user detail */
        $app->get('/users/{id}', 'RideTimeServer\API\Controllers\UserController:get');
        /** Update user */
        $app->put('/users/{id}', 'RideTimeServer\API\Controllers\UserController:update');
        /** Update user's picture */
        $app->post('/users/{id}/picture', 'RideTimeServer\API\Controllers\UserController:uploadPicture');
    }

    protected function initDashboardRoutes(App $app)
    {
        /** Get dashboard */
        $app->get('', 'RideTimeServer\API\Controllers\DashboardController:all');
        /** Request friendship */
        $app->post('/friends/{id}', 'RideTimeServer\API\Controllers\DashboardController:requestFriend');
        /** Accept friendship */
        $app->put('/friends/{id}/accept', 'RideTimeServer\API\Controllers\DashboardController:acceptFriend');
        /** Decline/Delete friendship */
        $app->delete('/friends/{id}', 'RideTimeServer\API\Controllers\DashboardController:removeFriend');
    }

    protected function initNotificationsRoutes(App $app)
    {
        /** Set notifications token */
        $app->put('/token', 'RideTimeServer\API\Controllers\NotificationsController:setToken');
        /** Send notification */
        // $app->post('', 'RideTimeServer\API\Controllers');
    }
}
