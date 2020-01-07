<?php
namespace RideTimeServer\API\Controllers;

use Doctrine\Common\Collections\Criteria;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use RideTimeServer\API\PictureHandler;
use RideTimeServer\Entities\User;
use RideTimeServer\Exception\UserException;

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
