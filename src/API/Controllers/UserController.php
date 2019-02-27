<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use Doctrine\ORM\EntityManager;
use RideTimeServer\API\Endpoints\UserEndpoint;
use RideTimeServer\API\Endpoints\EndpointInterface;

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
        $data = $this->processUserData($request);

        $endpoint = $this->getEndpoint();
        $user = $endpoint->get($args['id']);

        $result = $endpoint->update(
            $user,
            $data,
            $request->getAttribute('token')['sub']
        );

        // 200, there's no updated HTTP code
        return $response->withJson($endpoint->getDetail($result));
    }

    protected function processUserData(Request $request): array
    {
        $data = $request->getParsedBody();
        $data['picture'] = $this->processPicture($request);

        return $data;
    }

    protected function processPicture(Request $request): string
    {
        // First look for an uploaded picture
        if (!empty($request->getUploadedFiles()['picture'])) {
            // http://www.slimframework.com/docs/v3/cookbook/uploading-files.html
            $uploadedFile = $request->getUploadedFiles()['picture'];
            /**
             * { file, name, type }
             */
            var_dump($uploadedFile);
        // Then check URL
        } elseif (!empty($request->getParsedBody()['picture'])) {
            $url = $request->getParsedBody()['picture'];
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new UserException('Invalid picture URL', 400);
            }
            var_dump($url);
        } else {
            // $this->container['logger']->addInfo('Submitted user with no picture');
            var_dump('Submitted user with no picture');
            var_dump($request->getUploadedFiles());
            var_dump($request->getParsedBody());
        }
die();
        return '';
    }

    protected function getEndpoint(): EndpointInterface
    {
        return new UserEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
