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
        $user = $request->getAttribute('currentUser');

        $result = $this->getEndpoint()->listInvites($user);

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
        $eventId = $this->inputFilterId($args['id']);
        $userId = $this->inputFilterId($args['userId']);
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
        $eventId = $this->inputFilterId($args['id']);
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
        // TODO: Test and review: should be handled by error handler
        try {
            $result = $this->getEndpoint()->removeMember(
                $$this->inputFilterId($args['id']),
                (int) $request->getAttribute('currentUser')->getId()
            );
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

        $result = $this->getEndpoint()->removeMember(
            $this->inputFilterId($args['id']),
            $this->inputFilterId($args['userId'])
        );

        return $response->withStatus(200)->withJson(['status' => $result]);
    }

    public function acceptRequest(Request $request, Response $response, array $args): Response
    {
        // TODO: must be member of event
        // $currentUserId = (int) $request->getAttribute('currentUser')->getId();

        $result = $this->getEndpoint()->acceptRequest(
            $this->inputFilterId($args['id']),
            $this->inputFilterId($args['userId'])
        );

        return $response->withStatus(200)->withJson(['status' => $result]);
    }

    public function filter(Request $request, Response $response, array $args): Response
    {
        $params = $request->getQueryParams();
        return $response->withJson($this->getEndpoint()->filter($params));
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
