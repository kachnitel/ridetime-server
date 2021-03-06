<?php
namespace RideTimeServer\API\Controllers;

use Doctrine\Common\Collections\Criteria;
use RideTimeServer\API\Filters\EventFilter;
use RideTimeServer\API\Providers\EventProvider;
use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\API\Repositories\EventRepository;
use RideTimeServer\Entities\Comment;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Notifications;
use RideTimeServer\Entities\User;
use RideTimeServer\Entities\NotificationsToken;
use RideTimeServer\Exception\UserException;
use RideTimeServer\MembershipManager;

use function GuzzleHttp\json_decode;

class EventController extends BaseController
{
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function add(Request $request, Response $response, array $args): Response
    {
        // TODO: Validate input!
        $data = json_decode($request->getBody());
        $currentUser = $request->getAttribute('currentUser');

        /** @var EventRepository $repo */
        $repo = $this->getEventRepository();
        $event = $repo->create($data, $currentUser);
        $repo->saveEntity($event);

        return $response->withJson($event->getDetail())->withStatus(201);
    }

    /**
     * @param Request $request
     * @param Response condition$response
     * @param array $args
     * @return Response
     */
    public function get(Request $request, Response $response, array $args): Response
    {
        $event = $this->getEvent($args['id'], $request->getAttribute('currentUser'));

        return $response->withJson((object) [
            'result' => $event->getDetail(),
            'relatedEntities' => $event->getRelated()
        ]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @deprecated
     */
    public function list(Request $request, Response $response, array $args): Response
    {
        $filter = new EventFilter($this->getEntityManager());
        if (!empty($request->getQueryParam('ids'))) {
            $filter->id($request->getQueryParam('ids'));
        } else {
            $filter->dateStart('1 hour ago');
        }

        $provider = new EventProvider($this->getEventRepository());
        $provider->setUser($request->getAttribute('currentUser'));
        $result = $provider->filter($filter->getCriteria())->getValues();

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    /**
     * Supported filters:
     * - location[]
     * - difficulty[]
     * - dateStart
     * - dateEnd
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function filter(Request $request, Response $response, array $args): Response
    {
        $filters = $request->getQueryParams();
        $criteria = Criteria::create()
            ->orderBy(array('timestamp' => Criteria::ASC))
            ->setFirstResult(0)
            ->setMaxResults(20);

        $filter = new EventFilter($this->getEntityManager(), $criteria);
        $filter->apply($filters);

        $provider = new EventProvider($this->getEventRepository());
        $provider->setUser($request->getAttribute('currentUser'));
        $result = $provider->filter($criteria)->getValues();

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    public function getInvites(Request $request, Response $response, array $args): Response
    {
        $result = $this->listOwnEvents(
            Event::STATUS_INVITED,
            $request->getAttribute('currentUser')
        );

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    public function getRequests(Request $request, Response $response, array $args): Response
    {
        $result = $this->listOwnEvents(
            Event::STATUS_REQUESTED,
            $request->getAttribute('currentUser')
        );

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    protected function listOwnEvents(string $status, User $user): array
    {
        $ids = $user->getEvents($status)->getValues();

        $filter = new EventFilter($this->getEntityManager());
        $filter->id($ids);

        $provider = new EventProvider($this->getEventRepository());
        $provider->setUser($user);
        return $provider->filter($filter->getCriteria())->getValues();
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
        $currentUser = $request->getAttribute('currentUser');

        $event = $this->getEvent($args['id'], $currentUser);

        /** @var User $user */
        $user = $this->getUserRepository()
            ->get($args['userId']);

        $membership = $this->getMembershipManager()->invite($event, $user);
        $this->getEventRepository()->saveEntity($membership);

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

        return $response->withStatus(201)->withJson(['status' => $membership->getDetail()]);
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
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('currentUser');

        $event = $this->getEvent($args['id'], $currentUser);

        $membership = $this->getMembershipManager()->join($event, $currentUser);
        $this->getEventRepository()->saveEntity($membership);

        $tokens = $this->getMemberNotificationTokens($event, [$currentUser]);

        $notifications = new Notifications();
        if ($membership->getStatus() === Event::STATUS_CONFIRMED) {
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
        } else {
            $notifications->sendNotification(
                $tokens,
                $currentUser->getName() . ' wants to join ' . $event->getTitle(),
                $currentUser->getName() . ' requested to join ' . $event->getTitle(),
                (object) [
                    'type' => 'eventJoinRequested',
                    'from' => $currentUser->getId(),
                    'event' => $event->getDetail()
                ],
                'eventMember'
            );
        }

        return $response->withStatus(201)->withJson($membership->getDetail());
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
        $membership = $this->getMembershipManager()->removeMember(
            $this->getEventRepository()->get($args['id']),
            $request->getAttribute('currentUser')
        );
        $this->getEntityManager()->remove($membership);
        $this->getEntityManager()->flush();

        return $response->withStatus(204);
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

        $membership = $this->getMembershipManager()->removeMember(
            $this->getEventRepository()->get($args['id']),
            $this->getUserRepository()->get($args['userId'])
        );
        $this->getEntityManager()->remove($membership);
        $this->getEntityManager()->flush();

        return $response->withStatus(204);
    }

    public function acceptRequest(Request $request, Response $response, array $args): Response
    {
        // TODO: must be member of event

        $membership = $this->getMembershipManager()->acceptRequest(
            $this->getEventRepository()->get($args['id']),
            $this->getUserRepository()->get($args['userId'])
        );
        $this->getEventRepository()->saveEntity($membership);

        $currentUser = $request->getAttribute('currentUser');

        $notifications = new Notifications();
        $notifications->sendNotification(
            $membership->getUser()->getNotificationsTokens()->getValues(),
            'Join request accepted - ' . $membership->getEvent()->getTitle(),
            'Your request to join ' . $membership->getEvent()->getTitle()
                . ' has been accepted by ' . $currentUser->getName(),
            (object) [
                'type' => 'eventOwnRequestAccepted',
                'from' => $currentUser->getId(),
                'event' => $membership->getEvent()->getDetail()
            ],
            'eventComment'
        );

        $tokens = $this->getMemberNotificationTokens(
            $membership->getEvent(),
            [
                $currentUser,
                $membership->getUser()
            ]
        );

        $notifications->sendNotification(
            $tokens,
            $membership->getUser()->getName() . ' joined you for a ride!',
            $membership->getUser()->getName() . ' joined ' . $membership->getEvent()->getTitle(),
            (object) [
                'type' => 'eventMemberJoined',
                'from' => $currentUser->getId(),
                'event' => $membership->getEvent()->getDetail()
            ],
            'eventMember'
        );

        return $response->withStatus(200)->withJson(['status' => $membership->getDetail()]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function addComment(Request $request, Response $response, array $args): Response
    {
        /** @var User $currentUser */
        $currentUser = $request->getAttribute('currentUser');
        $event = $this->getEvent($args['id'], $currentUser);

        if (!$event->isMember($currentUser)) {
            throw new UserException(
                'Cannot comment on an event you aren\'t a member of',
                403
            );
        }

        $data = json_decode($request->getBody());
        $comment = new Comment();
        $comment->setMessage($data->message);
        $comment->setAuthor($currentUser);
        $comment->addSeenBy($currentUser);
        $comment->setEvent($event);
        $comment->setTimestamp(new \DateTime());

        $this->getEntityManager()->persist($comment);
        $this->getEntityManager()->flush();

        $tokens = $this->getMemberNotificationTokens($event, [$currentUser]);

        $notifications = new Notifications();
        $notifications->sendNotification(
            $tokens,
            $currentUser->getName() . ' commented on ' . $event->getTitle(),
            $comment->getMessage(),
            (object) [
                'type' => 'eventCommentAdded',
                'from' => $currentUser->getId(),
                'event' => $event->getDetail(),
                'comment' => $comment->getDetail()
            ],
            'eventComment'
        );

        return $response->withJson((object) [
            'result' => $comment->getDetail()
        ]);
    }

    public function getComments(Request $request, Response $response, array $args): Response
    {
        $provider = new EventProvider($this->getEventRepository());
        $provider->setUser($request->getAttribute('currentUser'));
        $event = $provider->get($args['id']);

        return $response->withJson((object) [
            'results' => $this->extractDetails($event->getComments()->getValues())
        ]);
    }

    public function getEventRequests(Request $request, Response $response, array $args): Response
    {
        $event = $this->getEvent($args['id'], $request->getAttribute('currentUser'));

        if (!$event->isMember($request->getAttribute('currentUser'))) {
            throw new UserException("Current user is not a member of {$args['id']}", 403);
        }

        return $response->withJson((object) [
            'results' => $this->extractDetails($event->getRequests())
        ]);
    }

    /**
     * Extract tokens of confirmed members
     * REVIEW: sendNotification should accept User[] instead of $tokens param
     *
     * @param Event $event
     * @param User[] $exclude
     * @return NotificationsToken[]
     */
    protected function getMemberNotificationTokens(Event $event, array $exclude = []): array
    {
        $tokens = [];
        $event->getMembers()
            ->filter(function (EventMember $membership) use ($exclude) {
                return ($membership->getStatus() === Event::STATUS_CONFIRMED) &&
                    (!in_array($membership->getUser(), $exclude));
            })
            ->map(function (EventMember $membership) use (&$tokens) {
                if ($membership->getUser()->getNotificationsTokens()->isEmpty()) {
                    return;
                }
                array_push(
                    $tokens,
                    ...$membership->getUser()->getNotificationsTokens()
                );
            })
            ->getValues();
        return $tokens;
    }

    protected function getMembershipManager(): MembershipManager
    {
        return new MembershipManager();
    }

    protected function getEvent(int $id, User $currentUser): Event
    {
        $provider = new EventProvider($this->getEventRepository());
        $provider->setUser($currentUser);
        return $provider->get($id);
    }
}
