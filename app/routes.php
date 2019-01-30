<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Kachnitel\RideTimeServer\Database\Users;
use Kachnitel\RideTimeServer\Database\Rides;

$app->get('/rides', function (Request $request, Response $response) {
  $this->logger->addInfo('GET rides');

  $rides = new Rides($this->db);

  return $response->withJson($rides->getRides());
});

$app->get('/users/{id}', function (Request $request, Response $response, array $args) {
  $this->logger->addInfo('GET users/{id}', $args);

  $userId = (int) filter_var($args['id'], FILTER_SANITIZE_NUMBER_INT);

  $users = new Users($this->db);

  return $response->withJson($users->getUser($userId));
});
