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
        $this->app->post('/signin', Controllers\AuthController::class . ':signIn');
        $this->app->post('/signup', Controllers\AuthController::class . ':signUp');

        $cuMiddleware = new CurrentUserMiddleware($this->app->getContainer());

        $this->app->group('/api', function (App $app) use ($cuMiddleware) {
            $app->group('', function (App $app) {
                $app->group('/events', function (App $app) { self::initEventRoutes($app); });
                $app->group('/users', function (App $app) { self::initUserRoutes($app); });
            })->add($cuMiddleware->getMiddleware(true));
            $app->group('/locations', function (App $app) { self::initLocationRoutes($app); });
        });

        $this->app->group('/dashboard', function (App $app) {
            self::initDashboardRoutes($app);
        })->add($cuMiddleware->getMiddleware(true));

        $this->app->group('/notifications', function (App $app) {
            self::initNotificationsRoutes($app);
        })->add($cuMiddleware->getMiddleware(true));
    }

    public static function initEventRoutes(App $app)
    {
        /** List events */
        $app->get('', Controllers\EventController::class . ':list');
        /** TODO: Filter events REVIEW: Heavily based off of list - look into merging $ids param into filter */
        $app->get('/filter', Controllers\EventController::class . ':filter');
        /** Get event detail */
        $app->get('/{id:[0-9]+}', Controllers\EventController::class . ':get');
        /** Create event */
        $app->post('', Controllers\EventController::class . ':add');
        /** - Membership routes - */
        /** List my invites */
        $app->get('/invites', Controllers\EventController::class . ':listInvites');
        /** Add event member */
        $app->post('/{id:[0-9]+}/invite/{userId:[0-9]+}', Controllers\EventController::class . ':invite');
        /** Request join / accept invite */
        $app->post('/{id:[0-9]+}/join', Controllers\EventController::class . ':join');
        /** Decline invite / leave event */
        $app->delete('/{id:[0-9]+}/invite', Controllers\EventController::class . ':leave');
        $app->delete('/{id:[0-9]+}/leave', Controllers\EventController::class . ':leave');
        /** Decline request / remove member (moderator only TODO:) */
        $app->delete('/{id:[0-9]+}/join/{userId:[0-9]+}', Controllers\EventController::class . ':remove');
        $app->delete('/{id:[0-9]+}/members/{userId:[0-9]+}', Controllers\EventController::class . ':remove');
        /** Accept request (moderator only TODO:) */
        $app->put('/{id:[0-9]+}/join/{userId:[0-9]+}', Controllers\EventController::class . ':acceptRequest');
    }

    public static function initLocationRoutes(App $app)
    {
        /** Nearby locations */
        $app->get('/nearby', Controllers\LocationController::class . ':nearby');
        /** Bounding box */
        $app->get('/bbox', Controllers\LocationController::class . ':bbox');
        /** Search */
        $app->get('/search', Controllers\LocationController::class . ':search');
        /** TODO: List location top routes and trails, tunnel through to Trailforks */
        $app->get('/{id:[0-9]+}/routes', Controllers\LocationController::class . ':routesByLocation');
        $app->get('/{id:[0-9]+}/trails', Controllers\LocationController::class . ':trailsByLocation');
        /** TODO: Search location trails and routes */
    }

    public static function initUserRoutes(App $app)
    {
        /** List users */
        $app->get('', Controllers\UserController::class . ':list');
        /** Search users */
        $app->get('/search', Controllers\UserController::class . ':search');
        /** Get user detail */
        $app->get('/{id:[0-9]+}', Controllers\UserController::class . ':get');
        /** Update user */
        $app->put('/{id:[0-9]+}', Controllers\UserController::class . ':update');
        /** Update user's picture */
        $app->post('/{id:[0-9]+}/picture', Controllers\UserController::class . ':uploadPicture');
        /** - Friendship - */
        /** Request friendship */
        $app->post('/friends/{id:[0-9]+}', Controllers\UserController::class . ':requestFriend');
        /** Accept friendship */
        $app->put('/friends/{id:[0-9]+}/accept', Controllers\UserController::class . ':acceptFriend');
        /** Decline/Delete friendship */
        $app->delete('/friends/{id:[0-9]+}', Controllers\UserController::class . ':removeFriend');
    }

    public static function initDashboardRoutes(App $app)
    {
        /** Get dashboard */
        $app->get('', Controllers\DashboardController::class . ':all');
    }

    public static function initNotificationsRoutes(App $app)
    {
        /** Set notifications token */
        $app->put('/token', Controllers\NotificationsController::class . ':setToken');
        /** Send notification */
        // $app->post('', Controllers');::class . '
    }
}
