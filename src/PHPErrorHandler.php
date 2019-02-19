<?php
namespace RideTimeServer;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;

class PHPErrorHandler {
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Throwable $error) {
        $errorData = [
            'status' => 'error',
            'errorId' => uniqid('err-php-'),
            'timestamp' => time()
        ];

        $errorDetail = [
            'trace' => $error->getTrace(),
            'code' => $error->getCode()
        ];

        $this->logger->log(
            Logger::ERROR,
            $error->getMessage(),
            array_merge($errorData, $errorDetail)
        );

        return $response
            ->withStatus(500)
            ->withJson($errorData);
    }
}
