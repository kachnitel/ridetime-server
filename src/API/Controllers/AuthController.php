<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RideTimeServer\API\Endpoints\UserEndpoint;
use RideTimeServer\API\Endpoints\EndpointInterface;
use RideTimeServer\API\PictureHandler;

class AuthController extends BaseController
{
    /**
     * Request data:
     * {
     *   "email": "ohooh@gmail.com",
     *   "email_verified": true,
     *   "family_name": "Na",
     *   "gender": "male",
     *   "given_name": "Kach",
     *   "locale": "en-GB",
     *   "name": "Kach Na",
     *   "nickname": "hah",
     *   "picture": "https://lh6.googleusercontent.com/-PsRsqMn9aHU/AAAAAAAAAAI/AAAAAAAAAAA/ACevoQNHk9xwM4StGhT42-2lCejGsz7AdA/mo/photo.jpg",
     *   "sub": "google-oauth2|666",
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

        $user = $this->getEndpoint()->findBy('email', $userEmail);
        // If user has new ID from auth, save it
        if (!in_array($authUserId, $user->getAuthIds())) {
            $this->getEndpoint()->addAuthId($user, $authUserId);
        }
        $result = $this->getEndpoint()->getDetail($user);

        return $response->withJson($result);
    }

    public function signUp(Request $request, Response $response, array $args): Response
    {
        $token = $request->getAttribute('token');
        $authUserId = $token['sub'];

        $data = $request->getParsedBody();

        if (!empty($data['picture'])) {
            $handler = new PictureHandler(
                $this->container['s3']['client'],
                $this->container['s3']['bucket']
            );
            // TODO: upload with id or do not use the id at all
            $data['picture'] = $handler->processPictureUrl($data['picture'], 0);
        }

        $data['authIds'] = $authUserId;
        $result = $this->getEndpoint()->add($data, $this->container['logger']);
        $status = 201;

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
