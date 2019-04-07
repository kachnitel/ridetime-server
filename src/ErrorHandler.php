<?php
namespace RideTimeServer;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Monolog\Logger;
use RideTimeServer\Exception\RTException;

class ErrorHandler {
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
        $errorInfo = [
            'status' => 'error'
        ];
        $errorDetail = [];

        if ($exception instanceof RTException) {
            $errorDetail['data'] = $exception->getData();
        }

        if ($this->isUserError($exception->getCode())) {
            $logLevel = Logger::INFO;

            $httpResponseCode = $exception->getCode();
            $errorInfo['message'] = $exception->getMessage();
            $errorInfo['code'] = $httpResponseCode;
        } else {
            $logLevel = Logger::ERROR;

            $httpResponseCode = 500;

            $errorInfo['errorId'] = uniqid('err-');
            $errorInfo['timestamp'] = time();

            $errorDetail['trace'] = $exception->getTrace();
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
