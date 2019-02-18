<?php
namespace RideTimeServer;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;


class ErrorHandler {
   public function __invoke(ServerRequestInterface $request, ResponseInterface $response, \Exception $exception) {
        return $response
            ->withStatus($exception->getCode() ?? 500)
            ->withJson([
                'status' => 'error',
                'message' => $exception->getMessage()
            ]);
   }
}
