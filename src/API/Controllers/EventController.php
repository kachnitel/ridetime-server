<?php
namespace RideTimeServer\API\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\API\Endpoints\Database\EventEndpoint;
use RideTimeServer\Exception\EntityNotFoundException;

class EventController extends BaseController
{
    public function listInvites(Request $request, Response $response, array $args): Response
    {
        // User rather than ID?
        $user = $request->getAttribute('currentUser');

        $eventEndpoint = $this->getEndpoint();
        $result = $eventEndpoint->listInvites($user);

        return $response->withJson($result);
    }

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
     * Join or accept invite
     *
     * TODO: Notifications to existing members
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
     * Leave event or decline invite
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function leave(Request $request, Response $response, array $args): Response
    {
        $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);
        $userId = (int) $request->getAttribute('currentUser')->getId();

        $eventEndpoint = $this->getEndpoint();
        // TODO: Test and review: should be handled by error handler
        try {
            $result = $eventEndpoint->removeMember($eventId, $userId);
        } catch (EntityNotFoundException $exception) {
            return $response->withStatus(404)->withJson([
                'status' => 'error',
                'message' => $exception->getMessage()
            ]);
        }

        return $response->withStatus(200)->withJson(['status' => $result]);
    }

    /**
     * Remove a member or delete request
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function remove(Request $request, Response $response, array $args): Response
    {
        // TODO: must be member of event
        // $currentUserId = (int) $request->getAttribute('currentUser')->getId();
        $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);
        $userId = (int) filter_var($args['userId'], FILTER_SANITIZE_NUMBER_INT);

        $eventEndpoint = $this->getEndpoint();
        $result = $eventEndpoint->removeMember($eventId, $userId);

        return $response->withStatus(200)->withJson(['status' => $result]);
    }

    public function acceptRequest(Request $request, Response $response, array $args): Response
    {
        // TODO: must be member of event
        // $currentUserId = (int) $request->getAttribute('currentUser')->getId();
        $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);
        $userId = (int) filter_var($args['userId'], FILTER_SANITIZE_NUMBER_INT);

        $eventEndpoint = $this->getEndpoint();
        $result = $eventEndpoint->acceptRequest($eventId, $userId);

        return $response->withStatus(200)->withJson(['status' => $result]);
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
