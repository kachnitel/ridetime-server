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
        $request = new Request(
            'DELETE',
            new Uri('http', 'www.test.ca'),
            new Headers([]),
            [],
            [],
            new Stream(fopen('php://memory', 'r+'))
        );
        $request = $request->withAttribute('currentUser', $mod);

        $controller->remove($request, new Response(), [
            'id' => $event->getId(),
            'userId' => $user->getId()
        ]);

        $this->assertNotContains($member, $event->getMembers());
    }
}
