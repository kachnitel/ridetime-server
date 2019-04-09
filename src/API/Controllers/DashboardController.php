<?php
namespace RideTimeServer\API\Controllers;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class DashboardController
{
    public function all(Request $request, Response $response, array $args): Response
    {
        return $response->withJson('dashboard');
    }
}
