<?php
namespace RideTimeServer;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;
use RideTimeServer\Exception\RTException;
use RideTimeServer\Exception\UserException;

class ErrorHandler
{
    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \Exception $exception
    ) {
        // Returned to Response
        $errorInfo = [
            'status' => 'error'
        ];
        // Logged
        $errorDetail = [];

        if ($exception instanceof RTException) {
            $errorDetail['data'] = $exception->getData();
        }
        $errorDetail['trace'] = $exception->getTrace();

        if ($this->isUserError($exception)) {
            $logLevel = Logger::INFO;
            $httpResponseCode = $exception->getCode() > 400 ? $exception->getCode() : 400;

            $errorInfo['message'] = $exception->getMessage();
            $errorInfo['code'] = $httpResponseCode;
        } else {
            $logLevel = Logger::ERROR;
            $httpResponseCode = 500;

            $errorInfo['errorId'] = uniqid('err-');
            $errorInfo['timestamp'] = time();

            $errorDetail['code'] = $exception->getCode();
        }

        $this->logger->log(
            $logLevel,
            $exception->getMessage(),
            [
                'info' => $errorInfo,
                'detail' => $errorDetail
            ]
        );

        return $response
            ->withStatus($httpResponseCode)
            ->withJson($errorInfo);
    }

    /**
     * @param integer $code
     * @return boolean
     */
    protected function isUserError(\Throwable $error)
    {
        return $error instanceof UserException;
    }
}
