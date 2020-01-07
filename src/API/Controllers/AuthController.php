<?php
namespace RideTimeServer\API\Controllers;

use Doctrine\Common\Collections\Criteria;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use RideTimeServer\API\PictureHandler;
use RideTimeServer\Entities\NotificationsToken;
use RideTimeServer\Entities\User;

use function GuzzleHttp\json_decode;

class AuthController extends BaseController
{
    public function signIn(Request $request, Response $response, array $args): Response
    {
        $token = $request->getAttribute('token');
        $authUserId = $token['sub'];

        $criteria = Criteria::create()->where(
            Criteria::expr()->eq('authId', $authUserId)
        );
        /** @var User $user */
        $user = $this->getUserRepository()->matching($criteria)->first();

        $result = $user
            ? (object) [
                'success' => true,
                'user' => $user->getDetail()
            ]
            : (object) [
                'success' => false,
                'errorCode' => 404
            ];

        if ($user && !empty(
            json_decode($request->getBody())->notificationsToken
        )) {
            $token = new NotificationsToken(
                json_decode($request->getBody())->notificationsToken
            );
            $token->setUser($user);
            $user->addNotificationsToken($token);
        }

        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();

        return $response->withJson($result);
    }

    public function signUp(Request $request, Response $response, array $args): Response
    {
        $token = $request->getAttribute('token');

        $data = json_decode($request->getBody());
        $userData = $data->userInfo;

        /**
         * TODO: Dedupe / UserController::update
         */
        if (!empty($userData->picture)) {
            $handler = new PictureHandler(
                $this->container['s3']['client'],
                $this->container['s3']['bucket']
            );
            // TODO: upload with id / need to flush entity first (and after updating :()
            $userData->picture = $handler->processPictureUrl($userData->picture, 0);
        }

        $user = $this->getUserRepository()->create($userData);

        if (!empty($data->notificationsToken)) {
            $notificationsToken = new NotificationsToken(
                $data->notificationsToken
            );
            $notificationsToken->setUser($user);
            $user->addNotificationsToken($notificationsToken);
        }

        $user->setAuthId($token['sub']);

        $this->getUserRepository()->saveEntity($user);

        return $response->withJson($user->getDetail())->withStatus(201);
    }
}
