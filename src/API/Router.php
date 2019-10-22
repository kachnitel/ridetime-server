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
            $app->group('/locations', function (App $app) use ($that) { $that->initLocationRoutes($app); });
            $app->group('/events', function (App $app) use ($that) { $that->initEventRoutes($app); });
            $app->group('/users', function (App $app) use ($that) { $that->initUserRoutes($app); });
        })->add($cuMiddleware->getMiddleware(true));

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
        $app->get('', 'RideTimeServer\API\Controllers\EventController:list');
        /** TODO: Filter events REVIEW: Heavily based off of list - look into merging $ids param into filter */
        $app->get('/filter', 'RideTimeServer\API\Controllers\EventController:filter');
        /** List my invites */
        $app->get('/invites', 'RideTimeServer\API\Controllers\EventController:listInvites');
        /** Get event detail */
        $app->get('/{id}', 'RideTimeServer\API\Controllers\EventController:get');
        /** Create event */
        $app->post('', 'RideTimeServer\API\Controllers\EventController:add');
        /** - Membership routes - */
        /** Add event member */
        $app->post('/{id}/invite/{userId}', 'RideTimeServer\API\Controllers\EventController:invite');
        /** Request join / accept invite */
        $app->post('/{id}/join', 'RideTimeServer\API\Controllers\EventController:join');
        /** Decline invite / leave event */
        $app->delete('/{id}/invite', 'RideTimeServer\API\Controllers\EventController:leave');
        $app->delete('/{id}/leave', 'RideTimeServer\API\Controllers\EventController:leave');
        /** Decline request / remove member (moderator only TODO:) */
        $app->delete('/{id}/join/{userId}', 'RideTimeServer\API\Controllers\EventController:remove');
        $app->delete('/{id}/members/{userId}', 'RideTimeServer\API\Controllers\EventController:remove');
        /** Accept request (moderator only TODO:) */
        $app->put('/{id}/join/{userId}', 'RideTimeServer\API\Controllers\EventController:acceptRequest');
    }

    protected function initLocationRoutes(App $app)
    {
        /** List locations */
        $app->get('', 'RideTimeServer\API\Controllers\LocationController:list');
        /** Nearby locations */
        $app->get('/nearby', 'RideTimeServer\API\Controllers\LocationController:nearby');
        /** Bounding box */
        $app->get('/bbox', 'RideTimeServer\API\Controllers\LocationController:bbox');
        /** Search */
        $app->get('/search', 'RideTimeServer\API\Controllers\LocationController:search');
        /** TODO: List location top routes and trails */
        // $app->get('/{id}/popular', 'RideTimeServer\API\Controllers\LocationController:popular');
        /** TODO: Search location trails and routes */
    }

    protected function initUserRoutes(App $app)
    {
        /** List users */
        $app->get('', 'RideTimeServer\API\Controllers\UserController:list');
        /** Search users */
        $app->get('/search', 'RideTimeServer\API\Controllers\UserController:search');
        /** Get user detail */
        $app->get('/{id}', 'RideTimeServer\API\Controllers\UserController:get');
        /** Update user */
        $app->put('/{id}', 'RideTimeServer\API\Controllers\UserController:update');
        /** Update user's picture */
        $app->post('/{id}/picture', 'RideTimeServer\API\Controllers\UserController:uploadPicture');
        /** - Friendship - */
        /** Request friendship */
        $app->post('/friends/{id}', 'RideTimeServer\API\Controllers\UserController:requestFriend');
        /** Accept friendship */
        $app->put('/friends/{id}/accept', 'RideTimeServer\API\Controllers\UserController:acceptFriend');
        /** Decline/Delete friendship */
        $app->delete('/friends/{id}', 'RideTimeServer\API\Controllers\UserController:removeFriend');
    }

    protected function initDashboardRoutes(App $app)
    {
        /** Get dashboard */
        $app->get('', 'RideTimeServer\API\Controllers\DashboardController:all');
    }

    protected function initNotificationsRoutes(App $app)
    {
        /** Set notifications token */
        $app->put('/token', 'RideTimeServer\API\Controllers\NotificationsController:setToken');
        /** Send notification */
        // $app->post('', 'RideTimeServer\API\Controllers');
    }
}
