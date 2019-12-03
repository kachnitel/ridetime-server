<?php
namespace RideTimeServer\Tests\API\Controllers;

use Monolog\Logger;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Uri;
use Slim\Http\Headers;
use Slim\Http\Stream;
use Slim\Http\Response;
use RideTimeServer\API\Controllers\EventController;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Entities\Event;
use RideTimeServer\Tests\API\APITestCase;

use function GuzzleHttp\json_decode;

class EventControllerTest extends APITestCase
{
    public function testFilterFindsMatchingRoute()
    {
        $container = new Container([
            'entityManager' => $this->entityManager,
            'logger' => new Logger('EventControllerTest')
        ]);
        $controller = new EventController($container);

        $event = $this->generateEvent(null, null, $this->generateLocation(1));
        $event->setDate(new \DateTime());
        $event->setDifficulty(1);
        $eventNoMatch = $this->generateEvent();
        $eventNoMatch->setDifficulty(2);
        $eventNoMatch2 = $this->generateEvent();
        $eventNoMatch2->setDate(new \DateTime('2 hours ago'));
        $eventNoMatch3 = $this->generateEvent();
        $eventNoMatch3->setDate(new \DateTime('2 hours'));
        $this->generateEvent(null, null, $this->generateLocation(2)); // No match 4 (location ID mismatch)
        $this->entityManager->flush();

        $request = $this->getRequest('GET')
            ->withQueryParams([
                'location' => [$event->getLocation()->getId()],
                'difficulty' => [$event->getDifficulty()],
                'dateStart' => (new \DateTime('1 hour ago'))->getTimestamp(),
                'dateEnd' => (new \DateTime('1 hour'))->format(DATE_W3C)
            ]);

        $response = $controller->filter($request, new Response(), []);

        $result = json_decode($response->getBody());
        $this->assertCount(1, $result->results);
        $this->assertEquals($event->getId(), $result->results[0]->id);
    }

    public function testRemoveMember()
    {
        $container = new Container([
            'entityManager' => $this->entityManager,
            'logger' => new Logger('EventControllerTest')
        ]);
        $controller = new EventController($container);

        // Setup entities
        $user = $this->generateUser(); // User being removed
        $event = $this->generateEvent();
        $mod = $event->getCreatedBy(); // User initiating the action

        $this->entityManager->flush();

        // Add user to event
        $member = new EventMember();
        $member->setEvent($event);
        $member->setUser($user);
        $member->setStatus(Event::STATUS_CONFIRMED);
        $event->addMember($member);
        $user->addEvent($member);

        // Create an empty request
        $request = $this->getRequest('DELETE');
        $request = $request->withAttribute('currentUser', $mod);

        $controller->remove($request, new Response(), [
            'id' => $event->getId(),
            'userId' => $user->getId()
        ]);

        $this->assertNotContains($member, $event->getMembers());
    }

    public function testAcceptRequest()
    {
        $container = new Container([
            'entityManager' => $this->entityManager,
            'logger' => new Logger('EventControllerTest')
        ]);
        $controller = new EventController($container);

        // Setup entities
        $user = $this->generateUser(); // User being accepted
        $event = $this->generateEvent();
        $mod = $event->getCreatedBy(); // User initiating the action

        $this->entityManager->flush();

        // Add user to event
        $member = new EventMember();
        $member->setEvent($event);
        $member->setUser($user);
        $member->setStatus(Event::STATUS_REQUESTED);
        $event->addMember($member);
        $user->addEvent($member);

        // Create an empty request
        $request = $this->getRequest('DELETE');
        $request = $request->withAttribute('currentUser', $mod);

        $controller->acceptRequest($request, new Response(), [
            'id' => $event->getId(),
            'userId' => $user->getId()
        ]);

        $this->assertEquals(Event::STATUS_CONFIRMED, $member->getStatus());
    }

    public function testJoin()
    {
        $currentUser = $this->generateUser();
        $event = $this->generateEvent();
        $this->entityManager->flush();

        $request = $this->getRequest('POST')->withAttribute('currentUser', $currentUser);

        $container = new Container([
            'entityManager' => $this->entityManager,
            'logger' => new Logger('EventControllerTest')
        ]);
        $controller = new EventController($container);

        $result = json_decode(
            $controller->join(
                $request,
                new Response(),
                ['id' => $event->getId()]
            )->getBody()
        );
        $this->assertEquals($currentUser->getId(), $result->userId);
        $this->assertEquals($event->getId(), $result->eventId);
        $this->assertEquals(Event::STATUS_CONFIRMED, $result->status);
    }

    public function getRequest(string $method): Request
    {
        return new Request(
            $method,
            new Uri('http', 'www.test.ca'),
            new Headers([]),
            [],
            [],
            new Stream(fopen('php://memory', 'r+'))
        );
    }
}
