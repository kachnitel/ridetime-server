<?php
namespace RideTimeServer\API;

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Client as Guzzle;
use Slim\Http\UploadedFile;

class PictureHandler
{
    /**
     * @var S3Client
     */
    protected $s3client;

    /**
     * @var string
     */
    protected $bucket;

    public function __construct(S3Client $client, string $bucket)
    {
        $this->s3client = $client;
        $this->bucket = $bucket;
    }

    public function processPictureUrl(string $url, int $userId): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new UserException('Invalid picture URL', 400);
        }

        // WORKAROUND: Need to actually download the file for easier S3 upload
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

        return $this->submitPictureToS3($fileSettings, $extension, $userId);
    }

    /**
     * Looks for a picture parameter for either an URL or a file and uploads it to S3
     *
     * @param UploadedFile $uploadedFile
     * @param int $userId
     * @return string|null
     */
    public function processPicture(UploadedFile $uploadedFile, int $userId): ?string
    {
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
        $fileSettings = [
            'SourceFile' => $uploadedFile->file,
            'ContentType' => $uploadedFile->getClientMediaType()
        ];

        return $this->submitPictureToS3($fileSettings, $extension, $userId);
    }

    protected function submitPictureToS3($fileSettings, $extension, $userId): string
    {
        $uploadSettings = array_merge($fileSettings, [
            'Bucket' => $this->bucket,
            'Key'    => 'profile-images/' . $userId . '-' . uniqid() . '.' . $extension,
            'ACL'    => 'public-read'
        ]);

        try {
            // Upload data.
            $result = $this->s3client->putObject($uploadSettings);

            $pictureUrl = $result['ObjectURL'];
        } catch (S3Exception $e) {
            throw $e; // TODO:
        }

        return $pictureUrl;
    }
}