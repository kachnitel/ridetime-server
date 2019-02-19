<?php
namespace RideTimeServer\Tests;

use PHPUnit\Framework\TestCase;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Headers;
use Slim\Http\Uri;
use Slim\Http\Stream;

class ErrorHandlerTestCase extends TestCase
{
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
            new Stream(fopen('php://temp', 'r'))
        );
    }

    protected function assertServerErrorResponseParams($response)
    {
        $this->assertObjectHasAttribute('errorId', $response);
        $this->assertNotEmpty($response->errorId);
        $this->assertEquals(time(), $response->timestamp);
        $this->assertEquals('error', $response->status);
    }
}
