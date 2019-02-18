<?php
namespace RideTimeServer\Tests;

use PHPUnit\Framework\TestCase;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use RideTimeServer\ErrorHandler;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Headers;
use Slim\Http\Uri;
use Slim\Http\Stream;

class ErrorHandlerTest extends TestCase
{
    public function testUserErrorLogged()
    {
        $logHandler = new TestHandler();
        $logger = new Logger('errorHandlerTest', [$logHandler]);
        $errorHandler = new ErrorHandler($logger);

        /** @var Response $response */
        $response = $errorHandler(
            $this->getRequest(),
            new Response(),
            new \Exception('Test user error message', 400)
        );

        $this->assertTrue($logHandler->hasRecord('Test user error message', Logger::INFO));
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testServerErrorLogged()
    {
        $logHandler = new TestHandler();
        $logger = new Logger('errorHandlerTest', [$logHandler]);
        $errorHandler = new ErrorHandler($logger);

        /** @var Response $response */
        $response = $errorHandler(
            $this->getRequest(),
            new Response(),
            new \Exception('Test server error message', 500)
        );

        $this->assertTrue($logHandler->hasRecord('Test server error message', Logger::ERROR));
        $this->assertEquals(500, $response->getStatusCode());

    }

    /**
     * @return Request
     */
    protected function getRequest(): Request
    {
        return new Request(
            'GET',
            new Uri('http', 'localhost'),
            new Headers([]),
            [],
            [],
            new Stream(fopen(__FILE__, 'r'))
        );
    }
}
