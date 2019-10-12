<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Doctrine\Common\Collections\Collection;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Friendship;
use RideTimeServer\API\Endpoints\Database\UserEndpoint;

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
     * TODO: Invites REVIEW: Seems easier to leave invites in Events controller
     * raising the question whether dashboard shouldn't just be a single route
     * "GET /dashboard" returning info about self and pending friendships,
     * or "GET /users/me" returning this
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
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
            'currentUser' => $this->getUserEndpoint()->getDetail($user),
            'requests' => array_values($fRequests->toArray()),
            'sentRequests' => array_values($sentRequests->toArray())
        ]);
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
}
