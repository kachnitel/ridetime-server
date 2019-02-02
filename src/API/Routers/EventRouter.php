<?php
namespace RideTimeServer\API\Routers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use RideTimeServer\Entities\User;
use RideTimeServer\Entities\Event;
use RideTimeServer\API\Endpoints\EventEndpoint;

use Slim\App;

class EventRouter implements RouterInterface
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function initRoutes()
    {
        /**
         * Create event
        */
        $this->app->post('/events', function (Request $request, Response $response) {
            $data = $request->getParsedBody();

            /**
             * @var Event $event
             * TODO: Validate input!
             */
            // Ride must be created by existing user
            $user = $this->entityManager->find('RideTimeServer\Entities\User', $data['created_by']);

            $event = new Event();
            $event->setTitle($data['title']);
            $event->setDescription($data['description']);
            $event->setDate(new DateTime($data['datetime']));
            $event->setCreatedBy($user);
            // Creating user automatically joins
            $event->addUser($user);

            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $result = (object) [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                // 'members' => $event->getUsers()
            ];

            return $response->withJson($result)->withStatus(201);
        });

        /**
         * Get event
         */
        $this->app->get('/events/{id}', function (Request $request, Response $response, array $args) {
            $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);

            $eventEndpoint = new EventEndpoint($this->entityManager);

            return $response->withJson($eventEndpoint->getDetail($eventId));
        });

        /**
         * Add event member
         */

        $this->app->post('/events/{id}/members', function (Request $request, Response $response, array $args) {
            $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);
            $event = $this->entityManager->find('RideTimeServer\Entities\Event', $eventId);

            $data = $request->getParsedBody();

            /**
             * @var Event $event
             * TODO: Validate input!
             */
            $user = $this->entityManager->find('RideTimeServer\Entities\User', $data['userId']);

            $event->addUser($user);

            $this->entityManager->persist($event);

            $this->entityManager->flush();

            $result = (object) [
                'id' => $event->getId(),
                'title' => $event->getTitle(),
                // 'members' => $event->getUsers()
            ];

            return $response->withJson($result)->withStatus(201);
        });
    }
}