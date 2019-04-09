<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use RideTimeServer\API\Endpoints\UserEndpoint;
use RideTimeServer\Exception\UserException;
use RideTimeServer\API\PictureHandler;
use RideTimeServer\Entities\User;
use Slim\Http\UploadedFile;

class UserController extends BaseController
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        /** @var UserEndpoint $endpoint */
        $endpoint = $this->getEndpoint();
        // FIXME: unnecessary, call update w/ ID and use User internally in Endpoint
        /** @var \RideTimeServer\Entities\User $user */
        $user = $endpoint->get($args['id']);
        $this->validateUser($request->getAttribute('currentUser'), $user);

        $data = $this->processUserData($request, $args['id']);

        $result = $endpoint->update($user, $data);

        // 200, there's no updated HTTP code
        return $response->withJson($endpoint->getDetail($result));
    }

    protected function processUserData(Request $request, int $userId): array
    {
        $data = $request->getParsedBody();
        if (!empty($data['picture'])) {
            $handler = new PictureHandler(
                $this->container['s3']['client'],
                $this->container['s3']['bucket']
            );
            $data['picture'] = $handler->processPictureUrl($data['picture'], $userId);
        }

        return $data ?? [];
    }

    public function uploadPicture(Request $request, Response $response, array $args): Response
    {
        /** @var UserEndpoint $endpoint */
        $endpoint = $this->getEndpoint();
        /** @var \RideTimeServer\Entities\User $user */
        $user = $endpoint->get($args['id']);
        $this->validateUser($request->getAttribute('currentUser'), $user);

        // First look for an uploaded picture
        // http://www.slimframework.com/docs/v3/cookbook/uploading-files.html
        if (empty($request->getUploadedFiles()['picture'])) {
            throw new UserException('Picture not found in request', 400);
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->getUploadedFiles()['picture'];
        $picture = $this->handleUploadPicture($uploadedFile, $args['id']);

        $result = $endpoint->update(
            $user,
            ['picture' => $picture]
        );

        return $response->withJson($endpoint->getDetail($result));
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
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function addFriend(Request $request, Response $response, array $args): Response
    {
        if ($request->getAttribute('currentUser')->getId() !== (int) $args['id']) {
            throw new UserException('ID must be same as current user', 403);
        }

        $endpoint = $this->getEndpoint();
        $result = $endpoint->addFriend($args['id'], $args['friendId']);

        return $response->withJson($endpoint->getDetail($result));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function acceptFriend(Request $request, Response $response, array $args): Response
    {
        if ($request->getAttribute('currentUser')->getId() !== (int) $args['friendId']) {
            throw new UserException('Friend ID must be same as current user', 403);
        }

        $endpoint = $this->getEndpoint();
        $result = $endpoint->acceptFriend($args['id'], $args['friendId']);

        return $response->withJson($endpoint->getDetail($result));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function removeFriend(Request $request, Response $response, array $args): Response
    {
        if (
            $request->getAttribute('currentUser')->getId() !== (int) $args['id'] &&
            $request->getAttribute('currentUser')->getId() !== (int) $args['friendId']
        ) {
            throw new UserException('ID or Friend ID must be same as current user', 403);
        }

        $endpoint = $this->getEndpoint();
        $result = $endpoint->removeFriend($args['id'], $args['friendId']);

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

    /**
     * Throw error uf users are not the same
     *
     * @param User $currentUser
     * @param User $user
     * @return void
     */
    protected function validateUser(?User $currentUser, User $user)
    {
        if ($currentUser === null) {
            throw new UserException('Cannot validate user', 400);
        }
        if ($user !== $currentUser) {
            $e = new UserException('Cannot update another user!', 403);
            $e->setData([
                'currentUser' => $currentUser->getId(),
                'user' => $user->getId()
            ]);
            throw $e;
        }
    }
}
