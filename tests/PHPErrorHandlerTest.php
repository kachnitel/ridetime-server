<?php
namespace RideTimeServer\Tests;

use Monolog\Logger;
use Monolog\Handler\TestHandler;
use RideTimeServer\PHPErrorHandler;
use Slim\Http\Response;

class PHPErrorHandlerTest extends ErrorHandlerTestCase
{
    public function testServerErrorLogged()
    {
        /** @var TestHandler $logHandler */
        $logHandler = new TestHandler();
        $logger = new Logger('phpErrorHandlerTest', [$logHandler]);
        $errorHandler = new PHPErrorHandler($logger);

        /** @var Response $response */
        $response = $errorHandler(
            $this->getRequest(),
            new Response(),
            new \Error('Test PHP error message')
        );

        $this->assertTrue($logHandler->hasRecord('Test PHP error message', Logger::ERROR));
        $this->assertNotEmpty($logHandler->getRecords()[0]['context']['detail']['trace']);
        $this->assertEquals(500, $response->getStatusCode());
    }

    /**
     * @group time-sensitive
     */
    public function testServerErrorResponseHasCorrectParams()
    {
        $logHandler = new TestHandler();
        $logger = new Logger('errorHandlerTest', [$logHandler]);
        $errorHandler = new PHPErrorHandler($logger);

        /** @var Response $response */
        $response = $errorHandler(
            $this->getRequest(),
            new Response(),
            new \Error('Test PHP error message')
        );

        $responseData = json_decode($response->getBody());

        $this->assertServerErrorResponseParams($responseData);
        $this->assertStringStartsWith('err-php-', $responseData->errorId);
    }
}
