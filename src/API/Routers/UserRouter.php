<?php
namespace RideTimeServer\API\Routers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use RideTimeServer\Entities\User;
use RideTimeServer\API\Endpoints\UserEndpoint;

use Slim\App;

class UserRouter implements RouterInterface
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

    /**
     * Initialize user routes
     *
     * @return void
     */
    public function initRoutes()
    {
        $this->app->post('/users', function (Request $request, Response $response) {
            $data = $request->getParsedBody();

            $userEndpoint = new UserEndpoint($this->entityManager);
            $user = $userEndpoint->add($data, $this->logger);

            return $response->withJson($user)->withStatus(201);
        });

        $this->app->get('/users/{id}', function (Request $request, Response $response, array $args) {
            $userId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);

            $userEndpoint = new UserEndpoint($this->entityManager);

            return $response->withJson($userEndpoint->getDetail($userEndpoint->get($userId)));
        });
    }
}