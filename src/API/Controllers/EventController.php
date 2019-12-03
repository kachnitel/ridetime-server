<?php
namespace RideTimeServer\API\Controllers;

use Doctrine\Common\Collections\Criteria;
use Slim\Http\Request;
use Slim\Http\Response;
use RideTimeServer\API\Repositories\EventRepository;
use RideTimeServer\Entities\Event;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Entities\Location;
use RideTimeServer\Notifications;
use RideTimeServer\Entities\User;
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
        $event = $this->getEventRepository()->get($args['id']);

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
     */
    public function list(Request $request, Response $response, array $args): Response
    {
        $result = $this->getEventRepository()->list($request->getQueryParam('ids'));

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
            ->orderBy(array('date' => Criteria::ASC))
            ->setFirstResult(0)
            ->setMaxResults(20);

        if (isset($filters['location'])) {
            $locations = [];
            foreach ($filters['location'] as $locationId) {
                $locations[] = $this->getEntityManager()->find(Location::class, $locationId);
            }
            $criteria = $criteria->andWhere(Criteria::expr()->in('location', $locations));
        }

        if (isset($filters['difficulty'])) {
            $values = array_map('intval', $filters['difficulty']);
            $criteria = $criteria->andWhere(Criteria::expr()->in('difficulty', $values));
        }

        if (isset($filters['dateStart'])) {
            $criteria = $criteria->andWhere(
                Criteria::expr()->gte('date', $this->getDateTimeObject($filters['dateStart']))
            );
        }

        if (isset($filters['dateEnd'])) {
            $criteria = $criteria->andWhere(
                Criteria::expr()->lte('date', $this->getDateTimeObject($filters['dateEnd']))
            );
        }

        $result = $this->getEventRepository()->matching($criteria)->getValues();

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
    }

    /**
     * @param [string|int] $date Date string or unix timestamp
     * @return \DateTime
     */
    protected function getDateTimeObject($date): \DateTime
    {
        return is_numeric($date)
            ? (new \DateTime())->setTimestamp($date)
            : new \DateTime($date);
    }

    public function listInvites(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('currentUser');
        $result = $user->getEvents(Event::STATUS_INVITED)->getValues();

        return $response->withJson((object) [
            'results' => $this->extractDetails($result)
        ]);
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
        $event = $this->getEventRepository()->get($args['id']);

        $user = $this->getEntityManager()
            ->getRepository(User::class)
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
        /** @var Event $event */
        $event = $this->getEventRepository()->get($args['id']);

        $membership = $this->getMembershipManager()->join($event, $currentUser);
        $this->getEventRepository()->saveEntity($membership);

        // Extract tokens of event members
        // REVIEW: sendNotification should accept User[] instead of $tokens param
        // TODO: filter confirmed members
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
            $this->getEntityManager()->getRepository(User::class)->get($args['userId'])
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
            $this->getEntityManager()->getRepository(User::class)->get($args['userId'])
        );
        $this->getEventRepository()->saveEntity($membership);

        return $response->withStatus(200)->withJson(['status' => $membership->getDetail()]);
    }

    protected function getEventRepository(): EventRepository
    {
        return $this->getEntityManager()
            ->getRepository(Event::class);
    }

    protected function getMembershipManager(): MembershipManager
    {
        return new MembershipManager();
    }
}
