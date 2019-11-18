<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RideTimeServer\API\Endpoints\Database\UserEndpoint;
use RideTimeServer\Exception\UserException;
use RideTimeServer\API\PictureHandler;
use RideTimeServer\Entities\User;
use Slim\Http\UploadedFile;
use RideTimeServer\Notifications;

class UserController extends BaseController
{
    use ValidateUserTrait;

    /**
     * Search using field:value formatted string
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
            throw new UserException('Search query must be in format key:search term');
        }
        $key = filter_var($search[0], FILTER_SANITIZE_STRING);
        $val = filter_var($search[1], FILTER_SANITIZE_STRING);

        $ep = $this->getEndpoint();
        $hits = $ep->search($key, $val);

        return $response->withJson($this->extractDetails($hits));
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $user = $this->validateUser($request, $args['id']);
        $data = $this->processUserData($request, $user);

        /** @var UserEndpoint $endpoint */
        $endpoint = $this->getEndpoint();
        $result = $endpoint->update($user, $data);

        // 200, there's no updated HTTP code
        return $response->withJson($result->getDetail());
    }

    protected function processUserData(Request $request, User $user): array
    {
        $data = $request->getParsedBody();
        if (!empty($data['picture']) && $user->getPicture() !== $data['picture']) {
            $handler = new PictureHandler(
                $this->container['s3']['client'],
                $this->container['s3']['bucket']
            );
            $data['picture'] = $handler->processPictureUrl($data['picture'], $user->getId());
        }

        return $data ?? [];
    }

    public function uploadPicture(Request $request, Response $response, array $args): Response
    {
        $user = $this->validateUser($request, $args['id']);

        // First look for an uploaded picture
        // http://www.slimframework.com/docs/v3/cookbook/uploading-files.html
        if (empty($request->getUploadedFiles()['picture'])) {
            throw new UserException('Picture not found in request', 400);
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->getUploadedFiles()['picture'];
        $picture = $this->handleUploadPicture($uploadedFile, $args['id']);

        /** @var UserEndpoint $endpoint */
        $endpoint = $this->getEndpoint();
        $result = $endpoint->update(
            $user,
            ['picture' => $picture]
        );

        return $response->withJson($result->getDetail());
    }

    protected function handleUploadPicture(UploadedFile $uploadedFile, int $id): ?string
    {
        if ($uploadedFile->getError() === 1) {
            $this->container['logger']->error('Error uploading file', [
                'filename' => $uploadedFile->getClientFilename(),
                'size' => $uploadedFile->getSize(),
                'type' => $uploadedFile->getClientMediaType(),
                'file' => $uploadedFile->file
            ]);
            throw new \Exception('Uploaded file error');
        }

        $handler = new PictureHandler(
            $this->container['s3']['client'],
            $this->container['s3']['bucket']
        );

        return $handler->processPicture($uploadedFile, $id);
    }

    /**
     * TODO: All friendship to UserCtrlr
     *
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function requestFriend(Request $request, Response $response, array $args): Response
    {
        $friendship = $this->getEndpoint()->addFriend(
            $request->getAttribute('currentUser')->getId(),
            $args['id']
        );

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
        $friendship = $this->getEndpoint()->acceptFriend(
            $args['id'],
            $request->getAttribute('currentUser')->getId()
        );

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
            $this->getEndpoint()->get($args['id'])
        );
        $this->container['entityManager']->remove($fs);
        $this->container['entityManager']->flush();

        return $response->withStatus(204);
    }

    /**
     * @return UserEndpoint
     */
    protected function getEndpoint()
    {
        return new UserEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
