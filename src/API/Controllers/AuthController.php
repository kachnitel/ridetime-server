<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use RideTimeServer\API\PictureHandler;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\UserException;

use function GuzzleHttp\json_decode;

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

        $data = json_decode($request->getBody());
        $userEmail = filter_var($data->email, FILTER_SANITIZE_EMAIL);

        /** @var User $user */
        $user = $this->getEntityManager()
            ->getRepository(User::class)
            ->findOneBy(['email' => $userEmail]);
        if (!$user) {
            return $response->withJson((object) [
                'success' => false,
                'errorCode' => 404
            ]);
        }

        $authUserId = $token['sub'];
        // Verify user from token
        if ($authUserId !== $user->getAuthId()) {
            $e = new UserException('Authentication ID mismatch', 400);
            $e->setData([
                'userId' => $user->getId(),
                'expectedId' => $user->getAuthId(),
                'requestTokenId' => $authUserId
            ]);

            throw $e;
        }

        $result = (object) [
            'success' => true,
            'user' => $user->getDetail()
        ];

        return $response->withJson($result);
    }

    public function signUp(Request $request, Response $response, array $args): Response
    {
        $token = $request->getAttribute('token');

        $data = json_decode($request->getBody());
        $data->authId = $token['sub'];

        /**
         * TODO: Dedupe / UserController::update
         */
        if (!empty($data->picture)) {
            $handler = new PictureHandler(
                $this->container['s3']['client'],
                $this->container['s3']['bucket']
            );
            // TODO: upload with id / need to flush entity first (and after updating :()
            $data->picture = $handler->processPictureUrl($data->picture, 0);
        }

        /** @var \RideTimeServer\API\Repositories\UserRepository $repo */
        $repo = $this->getEntityManager()
            ->getRepository(User::class);
        $user = $repo->create($data);
        $repo->saveEntity($user);

        return $response->withJson($user->getDetail())->withStatus(201);
    }
}
