<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RideTimeServer\Exception\UserException;
use RideTimeServer\API\PictureHandler;
use RideTimeServer\API\Repositories\UserRepository;
use RideTimeServer\Entities\User;
use Slim\Http\UploadedFile;
use RideTimeServer\Notifications;

use function GuzzleHttp\json_decode;

class UserController extends BaseController
{
    /**
     * Search using field:searchTerm formatted string
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function search(Request $request, Response $response, array $args): Response
    {
        $query = $request->getQueryParams();
        if (empty($query['q'])) {
            throw new UserException('Missing required parameter "q"');
        }

        $search = explode(':', $query['q'], 2);
        if (count($search) !== 2) {
            throw new UserException(
                'Search query must be in format key:search term. Invalid query: ' . $query['q']
            );
        }
        $field = filter_var($search[0], FILTER_SANITIZE_STRING);
        $searchTerm = filter_var($search[1], FILTER_SANITIZE_STRING);

        $repo = $this->getUserRepository();
        $hits = $repo->search($field, $searchTerm);

        return $response->withJson((object) [
            'results' => $this->extractDetails($hits)
        ]);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('currentUser');
        $this->validateSameId($user->getId(), $args['id']);

        $data = json_decode($request->getBody());
        if (!empty($data->picture)) {
            $data->picture = $this->processPictureFromUrl($data->picture, $user);
        }

        $this->getUserRepository()->update($user, $data);
        $this->getUserRepository()->saveEntity($user);

        // 200, there's no updated HTTP code
        return $response->withJson($user->getDetail());
    }

    /**
     * @param string $url
     * @param User $user
     * @return string
     */
    protected function processPictureFromUrl(string $url, User $user): string
    {
        if ($user->getPicture() !== $url) {
            $url = $this->getPictureHandler()->processPictureUrl($url, $user->getId());
        }

        return $url;
    }

    public function uploadPicture(Request $request, Response $response, array $args): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('currentUser');
        $this->validateSameId($user->getId(), $args['id']);

        // First look for an uploaded picture
        // http://www.slimframework.com/docs/v3/cookbook/uploading-files.html
        if (empty($request->getUploadedFiles()['picture'])) {
            throw new UserException('Picture not found in request', 400);
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->getUploadedFiles()['picture'];

        if ($uploadedFile->getError() === 1) {
            $this->container['logger']->error('Error uploading file', [
                'filename' => $uploadedFile->getClientFilename(),
                'size' => $uploadedFile->getSize(),
                'type' => $uploadedFile->getClientMediaType(),
                'file' => $uploadedFile->file
            ]);
            throw new \Exception('Uploaded file error');
        }

        $picture = $this->getPictureHandler()->processPicture($uploadedFile, $user->getId());

        $user->setPicture($picture);
        $this->getUserRepository()->saveEntity($user);

        return $response->withJson($user->getDetail());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function requestFriend(Request $request, Response $response, array $args): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('currentUser');
        $friendship = $user->addFriend($this->getUserRepository()->get($args['id']));
        $this->getUserRepository()->saveEntity($user);

        $notifications = new Notifications();
        $notifications->sendNotification(
            $friendship->getFriend()->getNotificationsTokens()->toArray(),
            'New friend request',
            $friendship->getUser()->getName() . ' wants to be your friend!',
            (object) [
                'type' => 'friendRequest',
                'from' => $friendship->getUser()->getId()
            ],
            'friendship'
        );

        return $response->withJson([
            'friendship' => $friendship->getDetail()
        ]);
    }

    /**
     * Accept friendship request from $args['id']
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function acceptFriend(Request $request, Response $response, array $args): Response
    {
        /** @var User $user */
        $user = $request->getAttribute('currentUser');
        $friendship = $user->acceptFriend($this->getUserRepository()->get($args['id']));
        $this->getUserRepository()->saveEntity($friendship);

        $notifications = new Notifications();
        $notifications->sendNotification(
            $friendship->getUser()->getNotificationsTokens()->toArray(),
            'Friend request accepted',
            $friendship->getFriend()->getName() . ' accepted your friend request!',
            (object) [
                'type' => 'friendRequestAccepted',
                'from' => $friendship->getFriend()->getId()
            ],
            'friendship'
        );

        return $response->withStatus(204);
    }

    /**
     * Delete friendship between current user and $args['id']
     * independent on who requested the friendship
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function removeFriend(Request $request, Response $response, array $args): Response
    {
        $fs = $request->getAttribute('currentUser')->removeFriend(
            $this->getUserRepository()->get($args['id'])
        );
        $this->getEntityManager()->remove($fs);
        $this->getEntityManager()->flush();

        return $response->withStatus(204);
    }

    protected function validateSameId(int $currentUserId, int $id)
    {
        if ($currentUserId !== $id) {
            $exception = new UserException('Cannot update other user than currentUser.', 400);
            $exception->setData(['ids' => func_get_args()]);
            throw $exception;
        }
    }

    protected function getUserRepository(): UserRepository
    {
        return $this->getEntityManager()->getRepository(User::class);
    }

    protected function getPictureHandler(): PictureHandler
    {
        return new PictureHandler(
            $this->container['s3']['client'],
            $this->container['s3']['bucket']
        );
    }
}
