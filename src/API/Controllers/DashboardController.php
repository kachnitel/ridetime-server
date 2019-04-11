<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RideTimeServer\Entities\User;
use Psr\Container\ContainerInterface;
use RideTimeServer\Entities\Friendship;
use Doctrine\Common\Collections\Collection;

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
}
