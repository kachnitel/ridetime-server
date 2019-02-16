<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RideTimeServer\API\Endpoints\UserEndpoint;
use RideTimeServer\API\Endpoints\EndpointInterface;
use Doctrine\ORM\EntityNotFoundException;

class AuthController extends BaseController
{
    /**
     * Request data:
     * {
     *   "email": "kachnitel@gmail.com",
     *   "email_verified": true,
     *   "family_name": "Na",
     *   "gender": "male",
     *   "given_name": "Kach",
     *   "locale": "en-GB",
     *   "name": "Kach Na",
     *   "nickname": "kachnitel",
     *   "picture": "https://lh6.googleusercontent.com/-PsRsqMn9aHU/AAAAAAAAAAI/AAAAAAAAAAA/ACevoQNHk9xwM4StGhT42-2lCejGsz7AdA/mo/photo.jpg",
     *   "sub": "google-oauth2|112817355155212920336",
     *   "updated_at": "2019-02-14T06:24:15.673Z",
     * }
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function signIn(Request $request, Response $response, array $args): Response
    {
        $token = $request->getAttribute('token');
        $authUserId = $token['sub'];

        $data = $request->getParsedBody();
        $userEmail = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

        try {
            $user = $this->getEndpoint()->findBy('email', $userEmail);
            $result = $this->getEndpoint()->getDetail($user);
            $status = 200;
        } catch (EntityNotFoundException $e) {
            $data['authId'] = $authUserId;
            $result = $this->getEndpoint()->add($data, $this->container['logger']);
            $status = 201;
        }

        return $response->withJson($result)->withStatus($status);
    }

    protected function getEndpoint(): EndpointInterface
    {
        return new UserEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
