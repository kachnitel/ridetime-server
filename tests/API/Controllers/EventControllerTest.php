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

        $this->entityManager->persist($user);
        $this->entityManager->persist($mod);
        $this->entityManager->persist($event);
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

        $this->entityManager->persist($user);
        $this->entityManager->persist($mod);
        $this->entityManager->persist($event);
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
        $this->entityManager->persist($currentUser);
        $this->entityManager->persist($event);
        $this->entityManager->flush([$currentUser, $event]);

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
