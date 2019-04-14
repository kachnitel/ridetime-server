<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RideTimeServer\API\Endpoints\UserEndpoint;
use RideTimeServer\Exception\UserException;
use RideTimeServer\API\PictureHandler;
use RideTimeServer\Entities\User;
use Slim\Http\UploadedFile;

class UserController extends BaseController
{
    use ValidateUserTrait;

    public function update(Request $request, Response $response, array $args): Response
    {
        $user = $this->validateUser($request, $args['id']);
        $data = $this->processUserData($request, $user);

        /** @var UserEndpoint $endpoint */
        $endpoint = $this->getEndpoint();
        $result = $endpoint->update($user, $data);

        // 200, there's no updated HTTP code
        return $response->withJson($endpoint->getDetail($result));
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
        $this->validateUser($request, (int) $args['id']);

        $endpoint = $this->getEndpoint();
        $endpoint->addFriend($args['id'], $args['friendId']);

        return $response->withStatus(204);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     *
     * @deprecated
     */
    public function acceptFriend(Request $request, Response $response, array $args): Response
    {
        $this->validateUser($request, (int) $args['friendId']);

        $endpoint = $this->getEndpoint();
        $endpoint->acceptFriend($args['id'], $args['friendId']);

        return $response->withStatus(204);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     * @deprecated
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
        $endpoint->removeFriend($args['id'], $args['friendId']);

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
