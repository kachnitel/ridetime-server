<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RideTimeServer\API\Endpoints\UserEndpoint;
use RideTimeServer\API\Endpoints\EndpointInterface;

class AuthController extends BaseController
{
    public function signIn(Request $request, Response $response, array $args): Response
    {
        $token = $request->getAttribute('token');
        $userEmail = filter_var($token['email'], FILTER_SANITIZE_EMAIL);
        $user = $this->getEndpoint()->findBy('email', $userEmail); // TODO:
        $userDetail = $this->getEndpoint()->getDetail($user);
        return $response->withJson($userDetail);
    }

    protected function getEndpoint(): EndpointInterface
    {
        return new UserEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
