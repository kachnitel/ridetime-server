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
        $cuMiddleware = new CurrentUserMiddleware($this->app->getContainer());

        /** Return user detail */
        $this->app->post('/signin', Controllers\AuthController::class . ':signIn');
        $this->app->post('/signup', Controllers\AuthController::class . ':signUp');
        $this->app->post('/signout', Controllers\AuthController::class . ':signOut')
            ->add($cuMiddleware->getMiddleware(true));

        $this->app->group('/api', function (App $app) use ($cuMiddleware) {
            $app->group('', function (App $app) {
                $app->group('/events', function (App $app) { self::initEventRoutes($app); });
                $app->group('/users', function (App $app) { self::initUserRoutes($app); });
                $app->group('/tracking', function (App $app) { self::initTrackingRoutes($app); });
                /** Search trails and routes */
                $app->get('/trails', Controllers\LocationController::class . ':trails');
                $app->get('/routes', Controllers\LocationController::class . ':routes');
            })->add($cuMiddleware->getMiddleware(true));
            // REVIEW: dirty. Should remove $requireUser param from CUMiddleware,
            // use separate route without user for sign up
            $app->group('/locations', function (App $app) { self::initLocationRoutes($app); })
                ->add($cuMiddleware->getMiddleware(false));
        });
    }

    public static function initEventRoutes(App $app)
    {
        /** List events */
        $app->get('', Controllers\EventController::class . ':list');
        /** Filter events REVIEW: Heavily based off of list - look into merging $ids param into filter */
        $app->get('/filter', Controllers\EventController::class . ':filter');
        /** Get event detail */
        $app->get('/{id:[0-9]+}', Controllers\EventController::class . ':get');
        /** Create event */
        $app->post('', Controllers\EventController::class . ':add');
        /** - Membership routes - */
        /** List my invites */
        $app->get('/invites', Controllers\EventController::class . ':getInvites');
        /** List my sent requests */
        $app->get('/requests', Controllers\EventController::class . ':getRequests');
        /** List pending requests */
        $app->get('/{id:[0-9]+}/requests', Controllers\EventController::class . ':getEventRequests');
        /** Add event member */
        $app->post('/{id:[0-9]+}/invite/{userId:[0-9]+}', Controllers\EventController::class . ':invite');
        /** Request join / accept invite */
        $app->post('/{id:[0-9]+}/join', Controllers\EventController::class . ':join');
        /** Decline invite / leave event */
        $app->delete('/{id:[0-9]+}/invite', Controllers\EventController::class . ':leave');
        $app->delete('/{id:[0-9]+}/leave', Controllers\EventController::class . ':leave');
        /** Decline request / remove member (moderator only TODO:) */
        $app->delete('/{id:[0-9]+}/requests/{userId:[0-9]+}', Controllers\EventController::class . ':remove');
        $app->delete('/{id:[0-9]+}/members/{userId:[0-9]+}', Controllers\EventController::class . ':remove');
        /** Accept request (moderator only TODO:) */
        $app->put('/{id:[0-9]+}/requests/{userId:[0-9]+}', Controllers\EventController::class . ':acceptRequest');
        /** - Comment routes - */
        $app->get('/{id:[0-9]+}/comments', Controllers\EventController::class . ':getComments');
        $app->post('/{id:[0-9]+}/comments', Controllers\EventController::class . ':addComment');
    }

    public static function initLocationRoutes(App $app)
    {
        /** Filter */
        $app->get('', Controllers\LocationController::class . ':filter');
        /** Get location detail */
        $app->get('/{id:[0-9]+}', Controllers\LocationController::class . ':get');
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
        /** List friends / requests */
        $app->get('/friends[/{status}[/{type}]]', Controllers\UserController::class . ':listFriends');
    }

    public static function initTrackingRoutes(App $app)
    {
        $app->get('', Controllers\TrackingController::class . ':list');
        $app->post('', Controllers\TrackingController::class . ':add');
        $app->delete('', Controllers\TrackingController::class . ':clear');
    }
}
