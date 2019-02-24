<?php
namespace RideTimeServer;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;

class ErrorHandler {
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Exception $exception) {
        $errorData = [
            'status' => 'error'
        ];

        if ($this->isUserError($exception->getCode())) {
            $httpResponseCode = $exception->getCode();
            $errorData['message'] = $exception->getMessage();
            $errorData['code'] = $httpResponseCode;
            $this->logger->log(Logger::INFO, $exception->getMessage());
        } else {
            $httpResponseCode = 500;

            $errorData['errorId'] = uniqid('err-');
            $errorData['timestamp'] = time();

            $errorDetail = [
                'trace' => $exception->getTrace(),
                'code' => $exception->getCode()
            ];

            $this->logger->log(
                Logger::ERROR,
                $exception->getMessage(),
                array_merge($errorData, $errorDetail)
            );
        }

        return $response
            ->withStatus($httpResponseCode)
            ->withJson($errorData);
    }

    /**
     * Return true if in 4xx range, otherwise return false
     *
     * @param integer $code
     * @return boolean
     */
    protected function isUserError(int $code)
    {
        return ($code >= 400 && $code < 500) ? true : false;
    }
}
