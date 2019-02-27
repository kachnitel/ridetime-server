<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface;
use RideTimeServer\API\Endpoints\UserEndpoint;
use RideTimeServer\API\Endpoints\EndpointInterface;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Client as Guzzle;
use RideTimeServer\Exception\UserException;

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
        /** @var \RideTimeServer\Entities\User $user */
        $user = $endpoint->get($args['id']);

        $data = $this->processUserData($request);

        $result = $endpoint->update(
            $user,
            $data,
            $request->getAttribute('token')['sub']
        );

        // 200, there's no updated HTTP code
        return $response->withJson($endpoint->getDetail($result));
    }

    public function uploadPicture(Request $request, Response $response, array $args): Response
    {
        /** @var UserEndpoint $endpoint */
        $endpoint = $this->getEndpoint();
        /** @var \RideTimeServer\Entities\User $user */
        $user = $endpoint->get($args['id']);

        $picture = $this->processPicture($request, $args['id']);

        if (empty($picture)) {
            throw new UserException('Processing picture returned empty url.', 400);
        }

        $result = $endpoint->update(
            $user,
            ['picture' => $picture],
            $request->getAttribute('token')['sub']
        );

        return $response->withJson($endpoint->getDetail($result));
    }

    protected function processUserData(Request $request): array
    {
        $data = $request->getParsedBody();
        if (!empty($data['picture'])) {
            throw new UserException('Picture must be submitted through POST /users/{id}/picture', 400);
        }

        return $data ?? [];
    }

    /**
     * Looks for a picture parameter for either an URL or a file and uploads it to S3
     *
     * @param Request $request
     * @return string|null
     */
    protected function processPicture(Request $request, int $userId): ?string
    {
        // First look for an uploaded picture
        // http://www.slimframework.com/docs/v3/cookbook/uploading-files.html
        if (!empty($request->getUploadedFiles()['picture'])) {
            /** @var \Slim\Http\UploadedFile $uploadedFile */
            $uploadedFile = $request->getUploadedFiles()['picture'];
            /**
             * { file, name, type }
             */
            $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);

            $fileSettings = [
                'SourceFile' => $uploadedFile->file,
                'ContentType' => $uploadedFile->getClientMediaType()
            ];
        // Then check URL
        } elseif (!empty($request->getParsedBody()['picture'])) {
            $url = $request->getParsedBody()['picture'];
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new UserException('Invalid picture URL', 400);
            }

            // Need to actually download the file for easier S3 upload
            // TODO: Try to implement direct upload
            // https://github.com/keboola/restbox-bundle/blob/master/Connectors/S3Connector.php#L184
            $guzzleClient = new Guzzle();
            $file = $guzzleClient->get($url);
            $info = getimagesizefromstring($file->getBody()->getContents());
            if (!is_int($info[2])) {
                throw new \Exception('Error getting picture information');
            }
            $extension = image_type_to_extension($info[2], false);

            $fileSettings = [
                'Body' => $file->getBody()
            ];
            if (!empty($file->getHeader('Content-Type'))) {
                $fileSettings['ContentType'] = $file->getHeader('Content-Type')[0];
            }
        } else {
            $this->container['logger']->addInfo('Submitted user with no picture');
            return null;
        }
        /** @var S3Client $s3client */
        $s3client = $this->container['s3']['client'];
        $s3bucket = $this->container['s3']['bucket'];

        $uploadSettings = array_merge($fileSettings, [
            'Bucket' => $s3bucket,
            'Key'    => 'profile-images/' . $userId . '-' . uniqid() . '.' . $extension,
            'ACL'    => 'public-read'
        ]);

        try {
            // Upload data.
            $result = $s3client->putObject($uploadSettings);

            $pictureUrl = $result['ObjectURL'];
        } catch (S3Exception $e) {
            throw $e; // TODO:
        }
        return $pictureUrl;
    }

    protected function getEndpoint(): EndpointInterface
    {
        return new UserEndpoint(
            $this->container->entityManager,
            $this->container->logger
        );
    }
}
