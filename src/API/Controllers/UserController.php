<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use RideTimeServer\API\Endpoints\UserEndpoint;
use RideTimeServer\Exception\UserException;
use RideTimeServer\API\PictureHandler;

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

        $data = $this->processUserData($request, $args['id']);

        $result = $endpoint->update(
            $user,
            $data,
            $request->getAttribute('token')['sub']
        );

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


        // First look for an uploaded picture
        // http://www.slimframework.com/docs/v3/cookbook/uploading-files.html
        if (empty($request->getUploadedFiles()['picture'])) {
            throw new UserException('Picture not found in request', 400);
        }

        /** @var \Slim\Http\UploadedFile $uploadedFile */
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

        $handler = new PictureHandler(
            $this->container['s3']['client'],
            $this->container['s3']['bucket']
        );
        $picture = $handler->processPicture($uploadedFile, $args['id']);

        $result = $endpoint->update(
            $user,
            ['picture' => $picture],
            $request->getAttribute('token')['sub']
        );

        return $response->withJson($endpoint->getDetail($result));
    }

    public function addFriend(Request $request, Response $response, array $args): Response
    {
        $endpoint = $this->getEndpoint();
        $result = $endpoint->addFriend($args['id'], $args['friendId']);

        return $response->withJson($endpoint->getDetail($result));
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
