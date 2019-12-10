<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Doctrine\Common\Collections\Collection;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Friendship;

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

    /**
     * REVIEW: Move to UserController "GET /users/me" ?
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @deprecated 0.5.7 In favor of UserController::listFriends
     */
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
            'currentUser' => $user->getDetail(),
            'requests' => array_values($fRequests->toArray()),
            'sentRequests' => array_values($sentRequests->toArray())
        ]);
    }

    protected function filterPendingRequests(Collection $friendships)
    {
        $filter = function(Friendship $friendship) {
            return $friendship->getStatus() === Friendship::STATUS_PENDING;
        };

        return $friendships->filter($filter);
    }
}
