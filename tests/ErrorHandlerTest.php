<?php
namespace RideTimeServer\Tests;

use Monolog\Logger;
use Monolog\Handler\TestHandler;
use RideTimeServer\ErrorHandler;
use Slim\Http\Response;

class ErrorHandlerTest extends ErrorHandlerTestCase
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

    public function testUserErrorResponseHasCorrectParams()
    {
        $logHandler = new TestHandler();
        $logger = new Logger('errorHandlerTest', [$logHandler]);
        $errorHandler = new ErrorHandler($logger);

        /** @var Response $response */
        $response = $errorHandler(
            $this->getRequest(),
            new Response(),
            new \Exception('User Error message', 400)
        );

        $responseData = json_decode($response->getBody());

        $this->assertEquals('User Error message', $responseData->message);
        $this->assertEquals('error', $responseData->status);
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
     * @group time-sensitive
     */
    public function testServerErrorResponseHasCorrectParams()
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

        $responseData = json_decode($response->getBody());

        $this->assertServerErrorResponseParams($responseData);
    }
}
