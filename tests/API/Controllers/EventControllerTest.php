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
use RideTimeServer\Entities\Comment;
use RideTimeServer\Entities\EventMember;
use RideTimeServer\Entities\Event;
use RideTimeServer\Exception\UserException;
use RideTimeServer\Tests\API\APITestCase;

use function GuzzleHttp\json_decode;

class EventControllerTest extends APITestCase
{
    public function testFilterFindsMatchingEvent()
    {
        $controller = $this->getEventController();

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
            ])
            ->withAttribute('currentUser', $event->getCreatedBy());

        $response = $controller->filter($request, new Response(), []);

        $result = json_decode($response->getBody());
        $this->assertCount(1, $result->results);
        $this->assertEquals($event->getId(), $result->results[0]->id);
    }

    public function testRemoveMember()
    {
        $controller = $this->getEventController();

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
        $controller = $this->getEventController();

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

        $controller = $this->getEventController();

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

    public function testAddComment()
    {
        $currentUser = $this->generateUser();
        $event = $this->generateEvent();
        $membership = new EventMember();
        $membership->setUser($currentUser);
        $membership->setEvent($event);
        $membership->setStatus(Event::STATUS_CONFIRMED);
        $event->addMember($membership);
        $this->entityManager->flush();

        $body = new Stream(fopen('php://memory', 'r+'));
        $body->write(json_encode(['message' => 'Test comment']));

        $request = $this->getRequest('POST')
            ->withAttribute('currentUser', $currentUser)
            ->withBody($body);

        $controller = $this->getEventController();

        $result = json_decode(
            $controller->addComment(
                $request,
                new Response(),
                ['id' => $event->getId()]
            )->getBody()
        )->result;

        $this->assertEquals(json_decode($request->getBody())->message, $result->message);
        $this->assertEquals($currentUser->getId(), $result->author);
        $this->assertEquals($event->getId(), $result->event);
    }

    public function testGet()
    {
        $event = $this->generateEvent();
        $event->setVisibility(Event::VISIBILITY_FRIENDS);
        $this->entityManager->flush();

        $controller = $this->getEventController();

        $request = $this->getRequest('GET')
            ->withAttribute('currentUser', $event->getCreatedBy());

        $result = json_decode(
            $controller->get(
                $request,
                new Response(),
                ['id' => $event->getId()]
            )->getBody()
        )->result;

        $this->assertEquals($event->getDetail(), $result);
    }

    public function testGetException()
    {
        $event = $this->generateEvent();
        $event->setVisibility(Event::VISIBILITY_FRIENDS);
        $user = $this->generateUser();
        $this->entityManager->flush();

        $controller = $this->getEventController();

        $request = $this->getRequest('GET')
            ->withAttribute('currentUser', $user);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage("Event {$event->getId()} is not visible to current user");
        $controller->get(
            $request,
            new Response(),
            ['id' => $event->getId()]
        );
    }

    public function testListByVisibility()
    {
        $event = $this->generateEvent(1);
        $privateEventByCurrentUser = $this->generateEvent(2)->setVisibility(Event::VISIBILITY_INVITED);
        $currentUser = $privateEventByCurrentUser->getCreatedBy();
        $privateEventByOther = $this->generateEvent(3)->setVisibility(Event::VISIBILITY_INVITED);
        $this->entityManager->flush();

        $controller = $this->getEventController();

        $request = $this->getRequest('GET')
            ->withAttribute('currentUser', $currentUser);

        $results = json_decode(
            $controller->list(
                $request,
                new Response(),
                []
            )->getBody()
        )->results;

        $this->assertContainsEquals($event->getDetail(), $results);
        $this->assertContainsEquals($privateEventByCurrentUser->getDetail(), $results);
        $this->assertNotContainsEquals($privateEventByOther->getDetail(), $results);
    }

    public function testFilterByVisibility()
    {
        $event = $this->generateEvent(1);
        $privateEventByCurrentUser = $this->generateEvent(2)->setVisibility(Event::VISIBILITY_INVITED);
        $currentUser = $privateEventByCurrentUser->getCreatedBy();
        $privateEventByOther = $this->generateEvent(3)->setVisibility(Event::VISIBILITY_INVITED);
        $this->entityManager->flush();

        $controller = $this->getEventController();

        $request = $this->getRequest('GET')
            ->withAttribute('currentUser', $currentUser);

        $results = json_decode(
            $controller->filter(
                $request,
                new Response(),
                []
            )->getBody()
        )->results;

        $this->assertContainsEquals($event->getDetail(), $results);
        $this->assertContainsEquals($privateEventByCurrentUser->getDetail(), $results);
        $this->assertNotContainsEquals($privateEventByOther->getDetail(), $results);
    }

    public function testGetComments()
    {
        $event = $this->generateEvent();
        $event->setVisibility(Event::VISIBILITY_FRIENDS);
        $event->addComment((new Comment())
            ->setMessage('text')
            ->setTimestamp(new \DateTime())
            ->setAuthor($event->getCreatedBy())
            ->setEvent($event)
        );
        $this->entityManager->flush();

        $controller = $this->getEventController();

        $request = $this->getRequest('GET')
            ->withAttribute('currentUser', $event->getCreatedBy());

        $results = json_decode(
            $controller->getComments(
                $request,
                new Response(),
                ['id' => $event->getId()]
            )->getBody()
        )->results;

        $this->assertEquals(
            $event->getComments()->first()->getMessage(),
            $results[0]->message
        );
    }

    public function testGetCommentsException()
    {
        $event = $this->generateEvent();
        $event->setVisibility(Event::VISIBILITY_FRIENDS);
        $user = $this->generateUser();
        $this->entityManager->flush();

        $controller = $this->getEventController();

        $request = $this->getRequest('GET')
            ->withAttribute('currentUser', $user);

        $this->expectException(UserException::class);
        $this->expectExceptionMessage("Event {$event->getId()} is not visible to current user");
        $controller->getComments(
            $request,
            new Response(),
            ['id' => $event->getId()]
        );
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

    protected function getEventController(): EventController
    {
        $container = new Container([
            'entityManager' => $this->entityManager,
            'logger' => new Logger('EventControllerTest')
        ]);
        return new EventController($container);
    }
}
