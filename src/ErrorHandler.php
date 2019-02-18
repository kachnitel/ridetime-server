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
        if ($this->isUserError($exception->getCode())) {
            $logLevel = Logger::INFO;
            $httpResponseCode = $exception->getCode();
        } else {
            $logLevel = Logger::ERROR;
            $httpResponseCode = 500;
        }
        $this->logger->log($logLevel, $exception->getMessage());

        return $response
            ->withStatus($httpResponseCode)
            ->withJson([
                'status' => 'error',
                'message' => $exception->getMessage()
            ]);
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
