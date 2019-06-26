<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RideTimeServer\API\Endpoints\Database\EventEndpoint;

class EventController extends BaseController
{
    /**
     * TODO: Notifications
     * TODO: currentUser Must be a member(or other status in the future) to invite
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function invite(Request $request, Response $response, array $args): Response
    {
        $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);
        $userId = (int) filter_var($args['userId'], FILTER_SANITIZE_NUMBER_INT);

        $eventEndpoint = $this->getEndpoint();
        $result = $eventEndpoint->invite($eventId, $userId);

        return $response->withStatus(201)->withJson(['status' => $result]);
    }

    /**
     * TODO: Notifications to members
     * should be possible to disable per event/member
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function join(Request $request, Response $response, array $args): Response
    {
        $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);
        $userId = (int) $request->getAttribute('currentUser')->getId();

        $eventEndpoint = $this->getEndpoint();
        $result = $eventEndpoint->join($eventId, $userId);

        return $response->withStatus(201)->withJson(['status' => $result]);
    }

    /**
     * @return EventEndpoint
     */
    protected function getEndpoint()
    {
        return new EventEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
