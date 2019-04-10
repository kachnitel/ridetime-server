<?php
namespace RideTimeServer\Tests;

use Monolog\Logger;
use Monolog\Handler\TestHandler;
use RideTimeServer\ErrorHandler;
use Slim\Http\Response;
use RideTimeServer\Exception\UserException;
use RideTimeServer\Exception\RTException;

class ErrorHandlerTest extends ErrorHandlerTestCase
{
    public function testUserErrorLogged()
    {
        $logHandler = new TestHandler();
        $response = $this->getErrorResponse(
            new UserException('Test user error message', 400),
            $logHandler
        );

        $this->assertTrue($logHandler->hasRecord('Test user error message', Logger::INFO));
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testUserErrorResponseHasCorrectParams()
    {
        $logHandler = new TestHandler();
        $response = $this->getErrorResponse(
            new UserException('User Error message', 400),
            $logHandler
        );

        $responseData = json_decode($response->getBody());

        $this->assertEquals('User Error message', $responseData->message);
        $this->assertEquals('error', $responseData->status);
    }

    public function testServerErrorLogged()
    {
        $logHandler = new TestHandler();
        $response = $this->getErrorResponse(
            new \Exception('Test server error message', 500),
            $logHandler
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
        $response = $this->getErrorResponse(
            new \Exception('Test server error message', 500),
            $logHandler
        );
        $responseData = json_decode($response->getBody());

        $this->assertServerErrorResponseParams($responseData);
        $this->assertStringStartsWith('err-', $responseData->errorId);
        $this->assertEquals(
            $logHandler->getRecords()[0]['context']['info']['errorId'],
            $responseData->errorId
        );
    }

    public function testDefaultUserErrorCode()
    {
        $response = $this->getErrorResponse(new UserException('Test user error message'));
        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testIsUserError()
    {
        $response = $this->getErrorResponse(new RTException('Test user error message', 400));
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * @param \Throwable $exception
     * @return Response
     */
    protected function getErrorResponse(\Throwable $exception, TestHandler $testLogHandler = null)
    {
        $logHandler = $testLogHandler ?? new TestHandler();
        $logger = new Logger('errorHandlerTest', [$logHandler]);
        $errorHandler = new ErrorHandler($logger);

        /** @var Response $response */
        return $errorHandler(
            $this->getRequest(),
            new Response(),
            $exception
        );
    }
}
