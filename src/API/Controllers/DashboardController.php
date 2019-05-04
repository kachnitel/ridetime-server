<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Doctrine\Common\Collections\Collection;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Friendship;
use RideTimeServer\API\Endpoints\Database\UserEndpoint;
use RideTimeServer\API\Endpoints\Database\NotificationsEndpoint;
use RideTimeServer\Notifications;

class DashboardController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function all(Request $request, Response $response, array $args): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('currentUser');

        $fRequests = $this->filterPendingRequests($user->getFriendshipsWithMe())
            ->map(function(Friendship $friendship) {
                return $friendship->getUser()->getId();
            });
        $sentRequests = $this->filterPendingRequests($user->getFriendships())
            ->map(function(Friendship $friendship) {
                return $friendship->getFriend()->getId();
            });

        return $response->withJson([
            'currentUser' => $this->getUserEndpoint()->getDetail($user),
            'requests' => array_values($fRequests->toArray()),
            'sentRequests' => array_values($sentRequests->toArray())
        ]);
    }

    public function requestFriend(Request $request, Response $response, array $args): Response
    {
        $friendship = $this->getUserEndpoint()->addFriend(
            $request->getAttribute('currentUser')->getId(),
            $args['id']
        );

        $notifications = new Notifications();
        $notifications->sendNotification(
            $friendship->getFriend()->getNotificationsTokens()->toArray(),
            'New friend request',
            $friendship->getUser()->getName() . ' wants to be your friend!',
            (object) [
                'type' => 'friendRequest',
                'from' => $friendship->getUser()->getId()
            ],
            'friendship'
        );

        return $response->withJson([
            'friendship' => $friendship->asObject()
        ]);
    }

    /**
     * Accept friendship request from $args['id']
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function acceptFriend(Request $request, Response $response, array $args): Response
    {
        $friendship = $this->getUserEndpoint()->acceptFriend(
            $args['id'],
            $request->getAttribute('currentUser')->getId()
        );

        $notifications = new Notifications();
        $notifications->sendNotification(
            $friendship->getUser()->getNotificationsTokens()->toArray(),
            'Friend request accepted',
            $friendship->getUser()->getName() . ' accepted your friend request!',
            (object) [
                'type' => 'friendRequestAccepted',
                'from' => $friendship->getFriend()->getId()
            ],
            'friendship'
        );

        return $response->withStatus(204);
    }

    /**
     * Delete friendship between current user and $args['id']
     * independent on who requested the friendship
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function removeFriend(Request $request, Response $response, array $args): Response
    {
        $fs = $request->getAttribute('currentUser')->removeFriend(
            $this->getUserEndpoint()->get($args['id'])
        );
        $this->container['entityManager']->remove($fs);
        $this->container['entityManager']->flush();

        return $response->withStatus(204);
    }

    protected function filterPendingRequests(Collection $friendships)
    {
        $filter = function(Friendship $friendship) {
            return $friendship->getStatus() === 0;
        };

        return $friendships->filter($filter);
    }

    /**
     * @return UserEndpoint
     */
    protected function getUserEndpoint(): UserEndpoint
    {
        return new UserEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }

    protected function getNotificationsEndpoint(): NotificationsEndpoint
    {
        return new NotificationsEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
