<?php
namespace RideTimeServer\API\Routers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

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
            // TODO: Validate input!
            $data = $request->getParsedBody();

            $eventEndpoint = new EventEndpoint($this->entityManager);
            $event = $eventEndpoint->add($data, $this->logger);

            return $response->withJson($event)->withStatus(201);
        });

        /**
         * Get event
         */
        $this->app->get('/events/{id}', function (Request $request, Response $response, array $args) {
            $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);

            $eventEndpoint = new EventEndpoint($this->entityManager);

            return $response->withJson($eventEndpoint->getDetail($eventEndpoint->get($eventId)));
        });

        /**
         * Add event member
         */
        $this->app->post('/events/{id}/members', function (Request $request, Response $response, array $args) {
            $eventId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);

            $data = $request->getParsedBody();
            $userId = (int) filter_var($data['userId'], FILTER_SANITIZE_NUMBER_INT);

            $eventEndpoint = new EventEndpoint($this->entityManager);
            $event = $eventEndpoint->get($eventId);

            $result = $eventEndpoint->addEventMember($event, $userId);

            return $response->withJson($result)->withStatus(201);
        });
    }
}
