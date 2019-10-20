<?php
namespace RideTimeServer\API\Controllers;

use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\API\Endpoints\Database\EventEndpoint;
use RideTimeServer\API\Endpoints\Database\UserEndpoint;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Exception\EntityNotFoundException;
use RideTimeServer\Notifications;
use RideTimeServer\Entities\User;

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
        $currentUser = $request->getAttribute('currentUser');
        $event = $this->getEndpoint()->get($eventId);
        $userEndpoint = new UserEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
        $user = $userEndpoint->get($userId);

        $result = $this->getEndpoint()->invite($eventId, $userId);

        $notifications = new Notifications();
        $notifications->sendNotification(
            $user->getNotificationsTokens()->toArray(),
            'New invite',
            $currentUser->getName() . ' invited you to ' . $event->getTitle(),
            (object) [
                'type' => 'eventInvite',
                'from' => $currentUser->getId(),
                'event' => $event->getDetail()
            ],
            'eventMember'
        );

        return $response->withStatus(201)->withJson(['status' => $result]);
    }

    /**
     * Join or accept invite
     *
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
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('currentUser');
        $event = $this->getEndpoint()->get($eventId);

        $result = $this->getEndpoint()->join($eventId, $currentUser->getId());

        // Extract tokens of event members
        $tokens = [];
        $event->getMembers()->map(function(EventMember $membership) use (&$tokens, $currentUser) {
            // Skip own notification tokens
            if ($membership->getUser() === $currentUser) {
                return;
            }
            array_push(
                $tokens,
                ...$membership->getUser()->getNotificationsTokens()
            );
        });

        $notifications = new Notifications();
        $notifications->sendNotification(
            $tokens,
            $currentUser->getName() . ' joined you for a ride!',
            $currentUser->getName() . ' joined ' . $event->getTitle(),
            (object) [
                'type' => 'eventMemberJoined',
                'from' => $currentUser->getId(),
                'event' => $event->getDetail()
            ],
            'eventMember'
        );

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
